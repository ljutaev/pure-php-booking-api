<?php

declare(strict_types=1);

namespace App\Application\Query\SearchHotels;

final class HotelListItem
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $city,
        public readonly string $country,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly int $stars,
        public readonly ?float $distanceKm,
        public readonly ?float $minPricePerNight,
    ) {
    }
}