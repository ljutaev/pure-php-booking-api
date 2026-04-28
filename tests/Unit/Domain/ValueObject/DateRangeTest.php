<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;
use App\Domain\ValueObject\DateRange;
use PHPUnit\Framework\TestCase;

class DateRangeTest extends TestCase
{
    public function testCreateValidDateRange(): void
    {
        $checkIn  = new \DateTimeImmutable('2026-06-01');
        $checkOut = new \DateTimeImmutable('2026-06-05');

        $range = new DateRange($checkIn, $checkOut);

        self::assertEquals($checkIn, $range->checkIn);
        self::assertEquals($checkOut, $range->checkOut);
    }

    public function testThrowsWhenCheckOutBeforeCheckIn(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new DateRange(
            new \DateTimeImmutable('2026-06-05'),
            new \DateTimeImmutable('2026-06-01'),
        );
    }

    public function testThrowsWhenCheckInEqualsCheckOut(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        $date = new \DateTimeImmutable('2026-06-01');
        new DateRange($date, $date);
    }

    public function testNightsCount(): void
    {
        $range = new DateRange(
            new \DateTimeImmutable('2026-06-01'),
            new \DateTimeImmutable('2026-06-05'),
        );

        self::assertSame(4, $range->nights());
    }

    public function testOverlapsReturnsTrueForOverlappingRanges(): void
    {
        $a = new DateRange(
            new \DateTimeImmutable('2026-06-01'),
            new \DateTimeImmutable('2026-06-05'),
        );
        $b = new DateRange(
            new \DateTimeImmutable('2026-06-03'),
            new \DateTimeImmutable('2026-06-07'),
        );

        self::assertTrue($a->overlaps($b));
        self::assertTrue($b->overlaps($a));
    }

    public function testOverlapsReturnsFalseForAdjacentRanges(): void
    {
        $a = new DateRange(
            new \DateTimeImmutable('2026-06-01'),
            new \DateTimeImmutable('2026-06-05'),
        );
        $b = new DateRange(
            new \DateTimeImmutable('2026-06-05'),
            new \DateTimeImmutable('2026-06-10'),
        );

        self::assertFalse($a->overlaps($b));
    }

    public function testOverlapsReturnsFalseForNonOverlappingRanges(): void
    {
        $a = new DateRange(
            new \DateTimeImmutable('2026-06-01'),
            new \DateTimeImmutable('2026-06-05'),
        );
        $b = new DateRange(
            new \DateTimeImmutable('2026-06-10'),
            new \DateTimeImmutable('2026-06-15'),
        );

        self::assertFalse($a->overlaps($b));
    }
}
