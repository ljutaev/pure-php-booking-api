<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Command;

use App\Application\Command\BookRoom\BookRoomCommand;
use App\Application\Command\BookRoom\BookRoomHandler;
use App\Domain\Entity\Booking;
use App\Domain\Entity\Room;
use App\Domain\Enum\RoomType;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\Exception\RoomNotAvailableException;
use App\Domain\Repository\BookingRepositoryInterface;
use App\Domain\Repository\RoomRepositoryInterface;
use App\Domain\ValueObject\BookingId;
use App\Domain\ValueObject\Capacity;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\GuestCount;
use App\Domain\ValueObject\HotelId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\RoomId;
use App\Domain\ValueObject\RoomNumber;
use App\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

class BookRoomHandlerTest extends TestCase
{
    private function makeRoom(int $capacity = 2): Room
    {
        return new Room(
            new RoomId('a47ac10b-58cc-4372-a567-0e02b2c3d479'),
            new HotelId('b47ac10b-58cc-4372-a567-0e02b2c3d479'),
            RoomType::Double,
            new RoomNumber('101'),
            new Capacity($capacity),
            new Money(15000, new Currency('USD')),
        );
    }

    private function makeCommand(int $guests = 2): BookRoomCommand
    {
        return new BookRoomCommand(
            userId: 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            roomId: 'a47ac10b-58cc-4372-a567-0e02b2c3d479',
            checkIn: '2026-06-01',
            checkOut: '2026-06-05',
            guests: $guests,
        );
    }

    public function testHandleReturnsBookingId(): void
    {
        $rooms = $this->createMock(RoomRepositoryInterface::class);
        $rooms->method('findById')->willReturn($this->makeRoom());

        $bookings = $this->createMock(BookingRepositoryInterface::class);
        $bookings->method('findByRoomAndDateRange')->willReturn([]);
        $bookings->expects($this->once())->method('save');

        $result = (new BookRoomHandler($rooms, $bookings))->handle($this->makeCommand());

        self::assertInstanceOf(BookingId::class, $result);
    }

    public function testHandleCalculatesTotalPriceCorrectly(): void
    {
        $rooms = $this->createMock(RoomRepositoryInterface::class);
        $rooms->method('findById')->willReturn($this->makeRoom()); // 150.00 USD/night

        $savedBooking = null;
        $bookings     = $this->createMock(BookingRepositoryInterface::class);
        $bookings->method('findByRoomAndDateRange')->willReturn([]);
        $bookings->method('save')->willReturnCallback(function (Booking $b) use (&$savedBooking): void {
            $savedBooking = $b;
        });

        (new BookRoomHandler($rooms, $bookings))->handle($this->makeCommand()); // 4 nights

        self::assertSame(60000, $savedBooking?->getTotalPrice()->amount); // 15000 * 4
    }

    public function testHandleThrowsWhenRoomUnderMaintenance(): void
    {
        $room = $this->makeRoom();
        $room->putUnderMaintenance();

        $rooms = $this->createMock(RoomRepositoryInterface::class);
        $rooms->method('findById')->willReturn($room);

        $bookings = $this->createMock(BookingRepositoryInterface::class);

        $this->expectException(RoomNotAvailableException::class);
        (new BookRoomHandler($rooms, $bookings))->handle($this->makeCommand());
    }

    public function testHandleThrowsWhenGuestsExceedCapacity(): void
    {
        $rooms = $this->createMock(RoomRepositoryInterface::class);
        $rooms->method('findById')->willReturn($this->makeRoom(capacity: 2));

        $bookings = $this->createMock(BookingRepositoryInterface::class);

        $this->expectException(BusinessRuleViolationException::class);
        (new BookRoomHandler($rooms, $bookings))->handle($this->makeCommand(guests: 3));
    }

    public function testHandleThrowsWhenDatesOverlapExistingBooking(): void
    {
        $rooms = $this->createMock(RoomRepositoryInterface::class);
        $rooms->method('findById')->willReturn($this->makeRoom());

        $existing = new Booking(
            BookingId::generate(),
            new UserId('f47ac10b-58cc-4372-a567-0e02b2c3d479'),
            new RoomId('a47ac10b-58cc-4372-a567-0e02b2c3d479'),
            new HotelId('b47ac10b-58cc-4372-a567-0e02b2c3d479'),
            new DateRange(new \DateTimeImmutable('2026-06-03'), new \DateTimeImmutable('2026-06-07')),
            new GuestCount(2),
            new Money(45000, new Currency('USD')),
            null,
        );

        $bookings = $this->createMock(BookingRepositoryInterface::class);
        $bookings->method('findByRoomAndDateRange')->willReturn([$existing]);

        $this->expectException(RoomNotAvailableException::class);
        (new BookRoomHandler($rooms, $bookings))->handle($this->makeCommand());
    }
}
