<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Domain\Entity\Hotel;
use App\Domain\Entity\Room;
use App\Domain\Enum\RoomType;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\Capacity;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\GeoPoint;
use App\Domain\ValueObject\GuestCount;
use App\Domain\ValueObject\HotelId;
use App\Domain\ValueObject\HotelName;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\RoomId;
use App\Domain\ValueObject\RoomNumber;
use App\Domain\ValueObject\StarRating;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Repository\Pdo\PdoHotelRepository;
use App\Infrastructure\Repository\Pdo\PdoRoomRepository;
use Tests\Integration\IntegrationTestCase;

final class PdoRoomRepositoryTest extends IntegrationTestCase
{
    private PdoRoomRepository $repository;
    private HotelId $hotelId;

    protected function setUp(): void
    {
        parent::setUp();

        $hotelRepo  = new PdoHotelRepository($this->pdo);
        $this->hotelId = HotelId::generate();
        $hotel = new Hotel(
            $this->hotelId,
            new HotelName('Test Hotel'),
            'desc',
            new Address('1 Main St', 'Kyiv', 'Ukraine', '01001'),
            new GeoPoint(50.45, 30.52),
            new StarRating(4),
            UserId::generate(),
        );
        $hotelRepo->save($hotel);

        $this->repository = new PdoRoomRepository($this->pdo);
    }

    public function testSaveAndFindById(): void
    {
        $room = $this->makeRoom('101');
        $this->repository->save($room);

        $found = $this->repository->findById($room->getId());

        $this->assertTrue($room->getId()->equals($found->getId()));
        $this->assertSame('101', $found->getNumber()->value);
        $this->assertSame(RoomType::Double, $found->getType());
        $this->assertSame(2, $found->getCapacity()->value);
        $this->assertSame(15000, $found->getPricePerNight()->amount);
        $this->assertSame('USD', $found->getPricePerNight()->currency->code);
    }

    public function testFindByIdThrowsWhenNotFound(): void
    {
        $this->expectException(EntityNotFoundException::class);

        $this->repository->findById(RoomId::generate());
    }

    public function testFindByHotelIdReturnsRoomsForHotel(): void
    {
        $room1 = $this->makeRoom('101');
        $room2 = $this->makeRoom('102');
        $this->repository->save($room1);
        $this->repository->save($room2);

        $rooms = $this->repository->findByHotelId($this->hotelId);
        $numbers = array_map(fn (Room $r) => $r->getNumber()->value, $rooms);

        $this->assertContains('101', $numbers);
        $this->assertContains('102', $numbers);
    }

    public function testFindAvailableRoomsReturnsAvailableRooms(): void
    {
        $room = $this->makeRoom('201');
        $this->repository->save($room);

        $dateRange = new DateRange(
            new \DateTimeImmutable('2026-06-01'),
            new \DateTimeImmutable('2026-06-05'),
        );

        $available = $this->repository->findAvailableRooms($this->hotelId, $dateRange, new GuestCount(2));

        $ids = array_map(fn (Room $r) => $r->getId()->value, $available);
        $this->assertContains($room->getId()->value, $ids);
    }

    private function makeRoom(string $number = '101'): Room
    {
        return new Room(
            RoomId::generate(),
            $this->hotelId,
            RoomType::Double,
            new RoomNumber($number),
            new Capacity(2),
            new Money(15000, new Currency('USD')),
        );
    }
}
