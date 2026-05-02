<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Pdo;

use App\Domain\Entity\Room;
use App\Domain\Enum\RoomStatus;
use App\Domain\Enum\RoomType;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\RoomRepositoryInterface;
use App\Domain\ValueObject\Capacity;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\GuestCount;
use App\Domain\ValueObject\HotelId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\RoomId;
use App\Domain\ValueObject\RoomNumber;

final class PdoRoomRepository implements RoomRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(Room $room): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO rooms
                (id, hotel_id, type, room_number, capacity, price_per_night, currency, status)
            VALUES
                (:id, :hotel_id, :type, :room_number, :capacity, :price_per_night, :currency, :status)
            ON CONFLICT (id) DO UPDATE SET
                hotel_id       = EXCLUDED.hotel_id,
                type           = EXCLUDED.type,
                room_number    = EXCLUDED.room_number,
                capacity       = EXCLUDED.capacity,
                price_per_night = EXCLUDED.price_per_night,
                currency       = EXCLUDED.currency,
                status         = EXCLUDED.status
        ');

        assert($stmt instanceof \PDOStatement);

        $stmt->execute([
            ':id'             => $room->getId()->value,
            ':hotel_id'       => $room->getHotelId()->value,
            ':type'           => $room->getType()->value,
            ':room_number'    => $room->getNumber()->value,
            ':capacity'       => $room->getCapacity()->value,
            ':price_per_night' => $room->getPricePerNight()->amount / 100,
            ':currency'       => $room->getPricePerNight()->currency->code,
            ':status'         => $room->getStatus()->value,
        ]);
    }

    public function findById(RoomId $id): Room
    {
        $stmt = $this->pdo->prepare('SELECT * FROM rooms WHERE id = :id');
        assert($stmt instanceof \PDOStatement);
        $stmt->execute([':id' => $id->value]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            throw new EntityNotFoundException("Room with id '{$id->value}' not found");
        }

        return $this->hydrate($row);
    }

    /** @return Room[] */
    public function findByHotelId(HotelId $hotelId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM rooms WHERE hotel_id = :hotel_id ORDER BY room_number');
        assert($stmt instanceof \PDOStatement);
        $stmt->execute([':hotel_id' => $hotelId->value]);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(fn (array $row) => $this->hydrate($row), $rows);
    }

    /** @return Room[] */
    public function findAvailableRooms(HotelId $hotelId, DateRange $dateRange, GuestCount $guests): array
    {
        $stmt = $this->pdo->prepare('
            SELECT r.*
            FROM rooms r
            WHERE r.hotel_id = :hotel_id
              AND r.status = :status
              AND r.capacity >= :guests
              AND NOT EXISTS (
                SELECT 1 FROM bookings b
                WHERE b.room_id = r.id
                  AND b.status != \'cancelled\'
                  AND b.check_in  < :check_out
                  AND b.check_out > :check_in
              )
            ORDER BY r.room_number
        ');

        assert($stmt instanceof \PDOStatement);

        $stmt->execute([
            ':hotel_id'  => $hotelId->value,
            ':status'    => RoomStatus::Available->value,
            ':guests'    => $guests->value,
            ':check_in'  => $dateRange->checkIn->format('Y-m-d'),
            ':check_out' => $dateRange->checkOut->format('Y-m-d'),
        ]);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(fn (array $row) => $this->hydrate($row), $rows);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Room
    {
        return Room::reconstitute(
            new RoomId((string) $row['id']),
            new HotelId((string) $row['hotel_id']),
            RoomType::from((string) $row['type']),
            new RoomNumber((string) $row['room_number']),
            new Capacity((int) $row['capacity']),
            new Money(
                (int) round((float) $row['price_per_night'] * 100),
                new Currency((string) $row['currency']),
            ),
            RoomStatus::from((string) $row['status']),
        );
    }
}
