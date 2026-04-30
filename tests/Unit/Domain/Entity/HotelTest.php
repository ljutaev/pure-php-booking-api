<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entity;

use App\Domain\Entity\Hotel;
use App\Domain\Enum\HotelStatus;
use App\Domain\Exception\BusinessRuleViolationException;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\GeoPoint;
use App\Domain\ValueObject\HotelId;
use App\Domain\ValueObject\HotelName;
use App\Domain\ValueObject\StarRating;
use App\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

class HotelTest extends TestCase
{
    private function makeHotel(): Hotel
    {
        return new Hotel(
            HotelId::generate(),
            new HotelName('Grand Palace'),
            'Luxury hotel in city center',
            new Address('Main St 1', 'Kyiv', 'UA', '01001'),
            new GeoPoint(50.45, 30.52),
            new StarRating(5),
            new UserId('f47ac10b-58cc-4372-a567-0e02b2c3d479'),
        );
    }

    public function testHotelIsActiveByDefault(): void
    {
        $hotel = $this->makeHotel();

        self::assertSame(HotelStatus::Active, $hotel->getStatus());
    }

    public function testDeactivateChangesStatusToInactive(): void
    {
        $hotel = $this->makeHotel();
        $hotel->deactivate();

        self::assertSame(HotelStatus::Inactive, $hotel->getStatus());
    }

    public function testActivateRestoresActiveStatus(): void
    {
        $hotel = $this->makeHotel();
        $hotel->deactivate();
        $hotel->activate();

        self::assertSame(HotelStatus::Active, $hotel->getStatus());
    }

    public function testSuspendChangesStatusToSuspended(): void
    {
        $hotel = $this->makeHotel();
        $hotel->suspend();

        self::assertSame(HotelStatus::Suspended, $hotel->getStatus());
    }

    public function testActivateAfterSuspend(): void
    {
        $hotel = $this->makeHotel();
        $hotel->suspend();
        $hotel->activate();

        self::assertSame(HotelStatus::Active, $hotel->getStatus());
    }

    public function testDeactivateThrowsWhenAlreadyInactive(): void
    {
        $hotel = $this->makeHotel();
        $hotel->deactivate();

        $this->expectException(BusinessRuleViolationException::class);
        $hotel->deactivate();
    }

    public function testActivateThrowsWhenAlreadyActive(): void
    {
        $hotel = $this->makeHotel();

        $this->expectException(BusinessRuleViolationException::class);
        $hotel->activate();
    }

    public function testGetters(): void
    {
        $id        = HotelId::generate();
        $name      = new HotelName('Test Hotel');
        $location  = new GeoPoint(50.45, 30.52);
        $rating    = new StarRating(4);
        $managerId = new UserId('f47ac10b-58cc-4372-a567-0e02b2c3d479');

        $hotel = new Hotel(
            $id,
            $name,
            'A description',
            new Address('St 1', 'City', 'UA', '00000'),
            $location,
            $rating,
            $managerId,
        );

        self::assertTrue($hotel->getId()->equals($id));
        self::assertSame($name->value, $hotel->getName()->value);
        self::assertSame('A description', $hotel->getDescription());
        self::assertSame(50.45, $hotel->getLocation()->latitude);
        self::assertSame(4, $hotel->getStarRating()->value);
        self::assertTrue($hotel->getManagerId()->equals($managerId));
        self::assertInstanceOf(\DateTimeImmutable::class, $hotel->getCreatedAt());
    }
}
