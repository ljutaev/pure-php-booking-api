<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;

final class GuestCount
{
    public readonly int $value;

    public function __construct(int $value)
    {
        if ($value < 1) {
            throw new InvalidValueObjectException(
                "Guest count must be at least 1, got: {$value}"
            );
        }

        $this->value = $value;
    }

    public function fitsIn(Capacity $capacity): bool
    {
        return $this->value <= $capacity->value;
    }
}
