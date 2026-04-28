<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    private Currency $usd;
    private Currency $eur;

    protected function setUp(): void
    {
        $this->usd = new Currency('USD');
        $this->eur = new Currency('EUR');
    }

    public function testCreateMoney(): void
    {
        $money = new Money(1000, $this->usd);

        self::assertSame(1000, $money->amount);
        self::assertTrue($money->currency->equals($this->usd));
    }

    public function testThrowsOnNegativeAmount(): void
    {
        $this->expectException(InvalidValueObjectException::class);

        new Money(-1, $this->usd);
    }

    public function testAddSameCurrency(): void
    {
        $a = new Money(1000, $this->usd);
        $b = new Money(500, $this->usd);

        $result = $a->add($b);

        self::assertSame(1500, $result->amount);
        self::assertTrue($result->currency->equals($this->usd));
    }

    public function testAddThrowsOnDifferentCurrency(): void
    {
        $a = new Money(1000, $this->usd);
        $b = new Money(500, $this->eur);

        $this->expectException(InvalidValueObjectException::class);

        $a->add($b);
    }

    public function testEqualsReturnsTrueForSameAmountAndCurrency(): void
    {
        $a = new Money(1000, $this->usd);
        $b = new Money(1000, $this->usd);

        self::assertTrue($a->equals($b));
    }

    public function testEqualsReturnsFalseForDifferentAmount(): void
    {
        $a = new Money(1000, $this->usd);
        $b = new Money(2000, $this->usd);

        self::assertFalse($a->equals($b));
    }

    public function testIsGreaterThan(): void
    {
        $a = new Money(2000, $this->usd);
        $b = new Money(1000, $this->usd);

        self::assertTrue($a->isGreaterThan($b));
        self::assertFalse($b->isGreaterThan($a));
    }

    public function testIsGreaterThanThrowsOnDifferentCurrency(): void
    {
        $a = new Money(2000, $this->usd);
        $b = new Money(1000, $this->eur);

        $this->expectException(InvalidValueObjectException::class);

        $a->isGreaterThan($b);
    }
}
