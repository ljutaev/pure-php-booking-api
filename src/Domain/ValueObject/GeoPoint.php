<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use App\Domain\Exception\InvalidValueObjectException;

final class GeoPoint
{
    public readonly float $latitude;
    public readonly float $longitude;

    public function __construct(float $latitude, float $longitude)
    {
        if ($latitude < -90.0 || $latitude > 90.0) {
            throw new InvalidValueObjectException(
                "Latitude must be between -90 and 90, got: {$latitude}"
            );
        }

        if ($longitude < -180.0 || $longitude > 180.0) {
            throw new InvalidValueObjectException(
                "Longitude must be between -180 and 180, got: {$longitude}"
            );
        }

        $this->latitude  = $latitude;
        $this->longitude = $longitude;
    }

    public function distanceTo(self $other): float
    {
        if ($this->latitude === $other->latitude && $this->longitude === $other->longitude) {
            return 0.0;
        }

        $earthRadius = 6371.0;

        $latFrom = deg2rad($this->latitude);
        $latTo   = deg2rad($other->latitude);
        $deltaLat = deg2rad($other->latitude - $this->latitude);
        $deltaLng = deg2rad($other->longitude - $this->longitude);

        $a = sin($deltaLat / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($deltaLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
