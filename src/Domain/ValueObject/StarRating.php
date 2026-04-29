<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;

final class StarRating
{
    public readonly int $value;

    public function __construct(int $value)
    {
        if ($value < 1 || $value > 5) {
            throw new InvalidValueObjectException(
                "Star rating must be between 1 and 5, got: {$value}"
            );
        }

        $this->value = $value;
    }

    public function isAtLeast(self $minimum): bool
    {
        return $this->value >= $minimum->value;
    }
}
