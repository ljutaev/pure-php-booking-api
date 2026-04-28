<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;

final class DateRange
{
    public readonly \DateTimeImmutable $checkIn;
    public readonly \DateTimeImmutable $checkOut;

    public function __construct(\DateTimeImmutable $checkIn, \DateTimeImmutable $checkOut)
    {
        if ($checkOut <= $checkIn) {
            throw new InvalidValueObjectException(
                'Check-out date must be after check-in date'
            );
        }

        $this->checkIn  = $checkIn;
        $this->checkOut = $checkOut;
    }

    public function nights(): int
    {
        return (int) $this->checkIn->diff($this->checkOut)->days;
    }

    public function overlaps(self $other): bool
    {
        return $this->checkIn < $other->checkOut
            && $other->checkIn < $this->checkOut;
    }
}
