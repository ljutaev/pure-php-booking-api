<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;

final class Capacity
{
    public readonly int $value;

    public function __construct(int $value)
    {
        if ($value < 1 || $value > 50) {
            throw new InvalidValueObjectException(
                "Capacity must be between 1 and 50, got: {$value}"
            );
        }

        $this->value = $value;
    }
}
