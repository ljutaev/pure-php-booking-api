<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Pdo;

use App\Domain\Entity\Booking;
use App\Domain\Enum\BookingStatus;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\BookingRepositoryInterface;
use App\Domain\ValueObject\BookingId;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\GuestCount;
use App\Domain\ValueObject\HotelId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\PaymentId;
use App\Domain\ValueObject\RoomId;
use App\Domain\ValueObject\UserId;

final class PdoBookingRepository implements BookingRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(Booking $booking): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO bookings
                (id, user_id, room_id, hotel_id, check_in, check_out,
                 guests, total_price, currency, status, special_requests, payment_id, created_at)
            VALUES
                (:id, :user_id, :room_id, :hotel_id, :check_in, :check_out,
                 :guests, :total_price, :currency, :status, :special_requests, :payment_id, :created_at)
            ON CONFLICT (id) DO UPDATE SET
                status           = EXCLUDED.status,
                payment_id       = EXCLUDED.payment_id,
                special_requests = EXCLUDED.special_requests
        ');

        assert($stmt instanceof \PDOStatement);

        $paymentId = $booking->getPaymentId();

        $stmt->execute([
            ':id'               => $booking->getId()->value,
            ':user_id'          => $booking->getUserId()->value,
            ':room_id'          => $booking->getRoomId()->value,
            ':hotel_id'         => $booking->getHotelId()->value,
            ':check_in'         => $booking->getDateRange()->checkIn->format('Y-m-d'),
            ':check_out'        => $booking->getDateRange()->checkOut->format('Y-m-d'),
            ':guests'           => $booking->getGuests()->value,
            ':total_price'      => $booking->getTotalPrice()->amount / 100,
            ':currency'         => $booking->getTotalPrice()->currency->code,
            ':status'           => $booking->getStatus()->value,
            ':special_requests' => $booking->getSpecialRequests(),
            ':payment_id'       => $paymentId?->value,
            ':created_at'       => $booking->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }

    public function findById(BookingId $id): Booking
    {
        $stmt = $this->pdo->prepare('SELECT * FROM bookings WHERE id = :id');
        assert($stmt instanceof \PDOStatement);
        $stmt->execute([':id' => $id->value]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            throw new EntityNotFoundException("Booking with id '{$id->value}' not found");
        }

        return $this->hydrate($row);
    }

    /** @return Booking[] */
    public function findByRoomAndDateRange(RoomId $roomId, DateRange $dateRange): array
    {
        $stmt = $this->pdo->prepare('
            SELECT * FROM bookings
            WHERE room_id   = :room_id
              AND status   != \'cancelled\'
              AND check_in  < :check_out
              AND check_out > :check_in
        ');

        assert($stmt instanceof \PDOStatement);

        $stmt->execute([
            ':room_id'   => $roomId->value,
            ':check_in'  => $dateRange->checkIn->format('Y-m-d'),
            ':check_out' => $dateRange->checkOut->format('Y-m-d'),
        ]);

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(fn (array $row) => $this->hydrate($row), $rows);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Booking
    {
        $paymentIdRaw = $row['payment_id'];

        return Booking::reconstitute(
            new BookingId((string) $row['id']),
            new UserId((string) $row['user_id']),
            new RoomId((string) $row['room_id']),
            new HotelId((string) $row['hotel_id']),
            new DateRange(
                new \DateTimeImmutable((string) $row['check_in']),
                new \DateTimeImmutable((string) $row['check_out']),
            ),
            new GuestCount((int) $row['guests']),
            new Money(
                (int) round((float) $row['total_price'] * 100),
                new Currency((string) $row['currency']),
            ),
            isset($row['special_requests']) && $row['special_requests'] !== null
                ? (string) $row['special_requests']
                : null,
            BookingStatus::from((string) $row['status']),
            $paymentIdRaw !== null ? new PaymentId((string) $paymentIdRaw) : null,
            new \DateTimeImmutable((string) $row['created_at']),
        );
    }
}
