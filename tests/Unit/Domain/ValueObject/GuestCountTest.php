<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;
use App\Domain\ValueObject\Capacity;
use App\Domain\ValueObject\GuestCount;
use PHPUnit\Framework\TestCase;

class GuestCountTest extends TestCase
{
    public function testCreateValidGuestCount(): void
    {
        $count = new GuestCount(3);

        self::assertSame(3, $count->value);
    }

    public function testThrowsOnZero(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new GuestCount(0);
    }

    public function testThrowsOnNegative(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new GuestCount(-1);
    }

    public function testFitsInReturnsTrueWhenWithinCapacity(): void
    {
        $guests   = new GuestCount(2);
        $capacity = new Capacity(3);

        self::assertTrue($guests->fitsIn($capacity));
    }

    public function testFitsInReturnsFalseWhenExceedsCapacity(): void
    {
        $guests   = new GuestCount(4);
        $capacity = new Capacity(3);

        self::assertFalse($guests->fitsIn($capacity));
    }
}
