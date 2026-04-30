<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;
use App\Domain\ValueObject\Capacity;
use PHPUnit\Framework\TestCase;

class CapacityTest extends TestCase
{
    public function testCreateValidCapacity(): void
    {
        $capacity = new Capacity(2);

        self::assertSame(2, $capacity->value);
    }

    public function testThrowsOnZero(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new Capacity(0);
    }

    public function testThrowsOnNegative(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new Capacity(-1);
    }

    public function testThrowsOnTooHigh(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new Capacity(51);
    }

    public function testBoundariesAreValid(): void
    {
        self::assertSame(1, (new Capacity(1))->value);
        self::assertSame(50, (new Capacity(50))->value);
    }
}
