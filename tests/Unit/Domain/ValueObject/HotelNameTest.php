<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;
use App\Domain\ValueObject\HotelName;
use PHPUnit\Framework\TestCase;

class HotelNameTest extends TestCase
{
    public function testCreateValidHotelName(): void
    {
        $name = new HotelName('Grand Palace');

        self::assertSame('Grand Palace', $name->value);
    }

    public function testThrowsOnEmptyName(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new HotelName('');
    }

    public function testThrowsOnWhitespaceOnly(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new HotelName('   ');
    }

    public function testThrowsWhenTooLong(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new HotelName(str_repeat('a', 256));
    }

    public function testMaxLengthIsValid(): void
    {
        $name = new HotelName(str_repeat('a', 255));

        self::assertSame(255, strlen($name->value));
    }
}
