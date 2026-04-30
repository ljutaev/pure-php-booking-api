<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\Room;
use App\Domain\Enum\RoomStatus;
use App\Domain\Enum\RoomType;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\Capacity;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\GuestCount;
use App\Domain\ValueObject\HotelId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\RoomId;
use App\Domain\ValueObject\RoomNumber;
use PHPUnit\Framework\TestCase;

class RoomTest extends TestCase
{
    private function makeRoom(): Room
    {
        return new Room(
            RoomId::generate(),
            new HotelId('f47ac10b-58cc-4372-a567-0e02b2c3d479'),
            RoomType::Double,
            new RoomNumber('101'),
            new Capacity(2),
            new Money(15000, new Currency('USD')),
        );
    }

    public function testRoomIsAvailableByDefault(): void
    {
        $room = $this->makeRoom();

        self::assertTrue($room->isAvailable());
        self::assertSame(RoomStatus::Available, $room->getStatus());
    }

    public function testPutUnderMaintenanceChangesStatus(): void
    {
        $room = $this->makeRoom();
        $room->putUnderMaintenance();

        self::assertFalse($room->isAvailable());
        self::assertSame(RoomStatus::Maintenance, $room->getStatus());
    }

    public function testMakeAvailableRestoresStatus(): void
    {
        $room = $this->makeRoom();
        $room->putUnderMaintenance();
        $room->makeAvailable();

        self::assertTrue($room->isAvailable());
    }

    public function testPutUnderMaintenanceThrowsWhenAlreadyUnderMaintenance(): void
    {
        $room = $this->makeRoom();
        $room->putUnderMaintenance();

        $this->expectException(BusinessRuleViolationException::class);
        $room->putUnderMaintenance();
    }

    public function testCanAccommodateReturnsTrueWhenWithinCapacity(): void
    {
        $room = $this->makeRoom(); // capacity 2

        self::assertTrue($room->canAccommodate(new GuestCount(2)));
    }

    public function testCanAccommodateReturnsFalseWhenExceedsCapacity(): void
    {
        $room = $this->makeRoom(); // capacity 2

        self::assertFalse($room->canAccommodate(new GuestCount(3)));
    }

    public function testGetters(): void
    {
        $id      = RoomId::generate();
        $hotelId = new HotelId('f47ac10b-58cc-4372-a567-0e02b2c3d479');
        $price   = new Money(15000, new Currency('USD'));

        $room = new Room($id, $hotelId, RoomType::Suite, new RoomNumber('102'), new Capacity(3), $price);

        self::assertTrue($room->getId()->equals($id));
        self::assertTrue($room->getHotelId()->equals($hotelId));
        self::assertSame(RoomType::Suite, $room->getType());
        self::assertSame('102', $room->getNumber()->value);
        self::assertSame(3, $room->getCapacity()->value);
        self::assertSame(15000, $room->getPricePerNight()->amount);
    }
}
