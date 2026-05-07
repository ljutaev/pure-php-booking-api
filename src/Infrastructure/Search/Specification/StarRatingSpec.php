<?php

declare(strict_types=1);

namespace App\Infrastructure\Search\Specification;

final class StarRatingSpec implements HotelSqlSpecificationInterface
{
    public function __construct(private readonly int $minimum)
    {
    }

    public function clause(): string
    {
        return 'stars >= :stars_min';
    }

    /** @return array<string, mixed> */
    public function params(): array
    {
        return [':stars_min' => $this->minimum];
    }
}