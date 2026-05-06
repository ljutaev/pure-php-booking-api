<?php

declare(strict_types=1);

namespace App\Infrastructure\Search\Specification;

final class LocationSpec implements HotelSqlSpecificationInterface
{
    public function __construct(
        private readonly float $latitude,
        private readonly float $longitude,
        private readonly float $radiusKm,
    ) {
    }

    public function clause(): string
    {
        return <<<'SQL'
            (6371 * acos(
                LEAST(1.0, cos(radians(:loc_lat)) * cos(radians(latitude))
                    * cos(radians(longitude) - radians(:loc_lon))
                    + sin(radians(:loc_lat)) * sin(radians(latitude)))
            )) <= :loc_radius
            SQL;
    }

    /** @return array<string, mixed> */
    public function params(): array
    {
        return [
            ':loc_lat'    => $this->latitude,
            ':loc_lon'    => $this->longitude,
            ':loc_radius' => $this->radiusKm,
        ];
    }
}