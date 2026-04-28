<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;

final class Money
{
    public readonly int $amount;
    public readonly Currency $currency;

    public function __construct(int $amount, Currency $currency)
    {
        if ($amount < 0) {
            throw new InvalidValueObjectException(
                "Money amount cannot be negative, got: {$amount}"
            );
        }

        $this->amount = $amount;
        $this->currency = $currency;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency->equals($other->currency);
    }

    public function isGreaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount > $other->amount;
    }

    private function assertSameCurrency(self $other): void
    {
        if (!$this->currency->equals($other->currency)) {
            throw new InvalidValueObjectException(
                "Cannot operate on different currencies: {$this->currency->code} and {$other->currency->code}"
            );
        }
    }
}
