<?php

declare(strict_types=1);

namespace App\Infrastructure\Search\Specification;

final class PriceRangeSpec implements HotelSqlSpecificationInterface
{
    public function __construct(
        private readonly ?float $min,
        private readonly ?float $max,
    ) {
    }

    public function clause(): string
    {
        $conditions = [];

        if ($this->min !== null) {
            $conditions[] = 'r.price_per_night >= :price_min';
        }

        if ($this->max !== null) {
            $conditions[] = 'r.price_per_night <= :price_max';
        }

        $inner = implode(' AND ', $conditions);

        return "EXISTS (SELECT 1 FROM rooms r WHERE r.hotel_id = hotels.id AND r.status = 'available' AND {$inner})";
    }

    /** @return array<string, mixed> */
    public function params(): array
    {
        $params = [];

        if ($this->min !== null) {
            $params[':price_min'] = $this->min;
        }

        if ($this->max !== null) {
            $params[':price_max'] = $this->max;
        }

        return $params;
    }
}
