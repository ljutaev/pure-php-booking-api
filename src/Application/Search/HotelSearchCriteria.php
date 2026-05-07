<?php

declare(strict_types=1);

namespace App\Application\Search;

final class HotelSearchCriteria
{
    public function __construct(
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly ?float $radiusKm = null,
        public readonly ?string $checkIn = null,
        public readonly ?string $checkOut = null,
        public readonly ?float $priceMin = null,
        public readonly ?float $priceMax = null,
        public readonly ?int $stars = null,
        public readonly int $page = 1,
        public readonly int $perPage = 20,
    ) {
    }

    public function hasGeoFilter(): bool
    {
        return $this->latitude !== null && $this->longitude !== null && $this->radiusKm !== null;
    }

    public function hasDateFilter(): bool
    {
        return $this->checkIn !== null && $this->checkOut !== null;
    }

    public function hasPriceFilter(): bool
    {
        return $this->priceMin !== null || $this->priceMax !== null;
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }
}