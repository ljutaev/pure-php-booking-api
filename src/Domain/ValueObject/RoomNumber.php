<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;

final class RoomNumber
{
    public readonly string $value;

    public function __construct(string $value)
    {
        if (trim($value) === '') {
            throw new InvalidValueObjectException('Room number cannot be empty');
        }

        $this->value = trim($value);
    }
}
