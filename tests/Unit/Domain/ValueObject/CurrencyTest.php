<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;
use App\Domain\ValueObject\Currency;
use PHPUnit\Framework\TestCase;

class CurrencyTest extends TestCase
{
    public function testCreateValidCurrency(): void
    {
        $currency = new Currency('USD');

        self::assertSame('USD', $currency->code);
    }

    public function testCreateNormalizesToUpperCase(): void
    {
        $currency = new Currency('usd');

        self::assertSame('USD', $currency->code);
    }

    public function testThrowsOnEmptyCode(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new Currency('');
    }

    public function testThrowsOnInvalidLength(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new Currency('US');
    }

    public function testEqualsReturnsTrueForSameCode(): void
    {
        $a = new Currency('EUR');
        $b = new Currency('EUR');

        self::assertTrue($a->equals($b));
    }

    public function testEqualsReturnsFalseForDifferentCode(): void
    {
        $a = new Currency('USD');
        $b = new Currency('EUR');

        self::assertFalse($a->equals($b));
    }
}
