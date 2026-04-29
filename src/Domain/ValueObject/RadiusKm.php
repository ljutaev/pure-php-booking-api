<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;

final class RadiusKm
{
    public readonly float $value;

    public function __construct(float $value)
    {
        if ($value <= 0.0) {
            throw new InvalidValueObjectException(
                "Radius must be positive, got: {$value}"
            );
        }

        $this->value = $value;
    }

    public function contains(GeoPoint $center, GeoPoint $point): bool
    {
        return $center->distanceTo($point) <= $this->value;
    }
}
