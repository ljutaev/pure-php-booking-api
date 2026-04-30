<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;

final class HotelName
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidValueObjectException('Hotel name cannot be empty');
        }

        if (strlen($trimmed) > 255) {
            throw new InvalidValueObjectException('Hotel name cannot exceed 255 characters');
        }

        $this->value = $trimmed;
    }
}
