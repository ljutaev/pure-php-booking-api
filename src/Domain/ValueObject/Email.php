<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;

final class Email
{
    public readonly string $value;

    public function __construct(string $email)
    {
        $normalized = strtolower(trim($email));

        if ($normalized === '') {
            throw new InvalidValueObjectException('Email cannot be empty');
        }

        if (filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidValueObjectException(
                "Invalid email format: '{$email}'"
            );
        }

        $this->value = $normalized;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
