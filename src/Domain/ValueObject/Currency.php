<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;

final class Currency
{
    public readonly string $code;

    public function __construct(string $code)
    {
        $normalized = strtoupper(trim($code));

        if (strlen($normalized) !== 3) {
            throw new InvalidValueObjectException(
                "Currency code must be 3 characters, got: '{$code}'"
            );
        }

        $this->code = $normalized;
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }
}
