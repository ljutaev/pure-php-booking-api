<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;
use App\Domain\ValueObject\BookingId;
use App\Domain\ValueObject\HotelId;
use App\Domain\ValueObject\PaymentId;
use App\Domain\ValueObject\ReviewId;
use App\Domain\ValueObject\RoomId;
use App\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

class EntityIdTest extends TestCase
{
    private const UUID = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';

    public function testCreateHotelId(): void
    {
        $id = new HotelId(self::UUID);

        self::assertSame(self::UUID, $id->value);
    }

    public function testCreateRoomId(): void
    {
        $id = new RoomId(self::UUID);

        self::assertSame(self::UUID, $id->value);
    }

    public function testCreateBookingId(): void
    {
        $id = new BookingId(self::UUID);

        self::assertSame(self::UUID, $id->value);
    }

    public function testCreateUserId(): void
    {
        $id = new UserId(self::UUID);

        self::assertSame(self::UUID, $id->value);
    }

    public function testCreatePaymentId(): void
    {
        $id = new PaymentId(self::UUID);

        self::assertSame(self::UUID, $id->value);
    }

    public function testCreateReviewId(): void
    {
        $id = new ReviewId(self::UUID);

        self::assertSame(self::UUID, $id->value);
    }

    public function testThrowsOnEmptyString(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new HotelId('');
    }

    public function testThrowsOnInvalidUuidFormat(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new HotelId('not-a-uuid');
    }

    public function testThrowsOnUuidWithWrongSegmentLengths(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new HotelId('f47ac10b-58cc-4372-a567-0e02b2c3d4'); // too short
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $a = new HotelId(self::UUID);
        $b = new HotelId(self::UUID);

        self::assertTrue($a->equals($b));
    }

    public function testEqualsReturnsFalseForDifferentValue(): void
    {
        $a = new HotelId(self::UUID);
        $b = new HotelId('a47ac10b-58cc-4372-a567-0e02b2c3d479');

        self::assertFalse($a->equals($b));
    }

    public function testGenerateCreatesValidUuid(): void
    {
        $id = HotelId::generate();

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $id->value
        );
    }

    public function testGenerateCreatesDifferentIds(): void
    {
        $a = HotelId::generate();
        $b = HotelId::generate();

        self::assertFalse($a->equals($b));
    }

    public function testTypeIsolation(): void
    {
        $hotelId = new HotelId(self::UUID);
        $roomId  = new RoomId(self::UUID);

        // Same UUID value but different types — not interchangeable
        self::assertNotInstanceOf(RoomId::class, $hotelId);
        self::assertNotInstanceOf(HotelId::class, $roomId);
    }
}
