<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Domain\Entity\Booking;
use App\Domain\Entity\Hotel;
use App\Domain\Entity\Room;
use App\Domain\Enum\BookingStatus;
use App\Domain\Enum\RoomType;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\BookingId;
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
use App\Infrastructure\Repository\Pdo\PdoBookingRepository;
use App\Infrastructure\Repository\Pdo\PdoHotelRepository;
use App\Infrastructure\Repository\Pdo\PdoRoomRepository;
use Tests\Integration\IntegrationTestCase;

final class PdoBookingRepositoryTest extends IntegrationTestCase
{
    private PdoBookingRepository $repository;
    private HotelId $hotelId;
    private RoomId $roomId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hotelId = HotelId::generate();
        $this->roomId  = RoomId::generate();

        $hotelRepo = new PdoHotelRepository($this->pdo);
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

        $roomRepo = new PdoRoomRepository($this->pdo);
        $room = new Room(
            $this->roomId,
            $this->hotelId,
            RoomType::Double,
            new RoomNumber('101'),
            new Capacity(2),
            new Money(15000, new Currency('USD')),
        );
        $roomRepo->save($room);

        $this->repository = new PdoBookingRepository($this->pdo);
    }

    public function testSaveAndFindById(): void
    {
        $booking = $this->makeBooking('2026-07-01', '2026-07-05');
        $this->repository->save($booking);

        $found = $this->repository->findById($booking->getId());

        $this->assertTrue($booking->getId()->equals($found->getId()));
        $this->assertSame(BookingStatus::Pending, $found->getStatus());
        $this->assertSame(60000, $found->getTotalPrice()->amount);
        $this->assertSame('USD', $found->getTotalPrice()->currency->code);
        $this->assertSame(2, $found->getGuests()->value);
        $this->assertNull($found->getSpecialRequests());
    }

    public function testSaveUpdatesStatus(): void
    {
        $booking = $this->makeBooking('2026-07-10', '2026-07-12');
        $this->repository->save($booking);

        $booking->confirm();
        $this->repository->save($booking);

        $found = $this->repository->findById($booking->getId());
        $this->assertSame(BookingStatus::Confirmed, $found->getStatus());
    }

    public function testFindByIdThrowsWhenNotFound(): void
    {
        $this->expectException(EntityNotFoundException::class);

        $this->repository->findById(BookingId::generate());
    }

    public function testFindByRoomAndDateRangeReturnsOverlappingBookings(): void
    {
        $booking = $this->makeBooking('2026-08-01', '2026-08-07');
        $this->repository->save($booking);

        $searchRange = new DateRange(
            new \DateTimeImmutable('2026-08-04'),
            new \DateTimeImmutable('2026-08-10'),
        );

        $found = $this->repository->findByRoomAndDateRange($this->roomId, $searchRange);

        $this->assertCount(1, $found);
        $this->assertTrue($booking->getId()->equals($found[0]->getId()));
    }

    public function testFindByRoomAndDateRangeExcludesCancelledBookings(): void
    {
        $booking = $this->makeBooking('2026-09-01', '2026-09-05');
        $booking->cancel();
        $this->repository->save($booking);

        $searchRange = new DateRange(
            new \DateTimeImmutable('2026-09-02'),
            new \DateTimeImmutable('2026-09-04'),
        );

        $found = $this->repository->findByRoomAndDateRange($this->roomId, $searchRange);

        $this->assertCount(0, $found);
    }

    public function testFindByRoomAndDateRangeReturnsEmptyForNonOverlapping(): void
    {
        $booking = $this->makeBooking('2026-10-01', '2026-10-05');
        $this->repository->save($booking);

        $searchRange = new DateRange(
            new \DateTimeImmutable('2026-10-06'),
            new \DateTimeImmutable('2026-10-10'),
        );

        $found = $this->repository->findByRoomAndDateRange($this->roomId, $searchRange);

        $this->assertCount(0, $found);
    }

    private function makeBooking(string $checkIn, string $checkOut): Booking
    {
        $dateRange  = new DateRange(
            new \DateTimeImmutable($checkIn),
            new \DateTimeImmutable($checkOut),
        );
        $nights     = $dateRange->nights();
        $totalPrice = new Money(15000 * $nights, new Currency('USD'));

        return new Booking(
            BookingId::generate(),
            UserId::generate(),
            $this->roomId,
            $this->hotelId,
            $dateRange,
            new GuestCount(2),
            $totalPrice,
            null,
        );
    }
}
