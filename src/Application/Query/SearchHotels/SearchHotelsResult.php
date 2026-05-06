<?php

declare(strict_types=1);

namespace App\Application\Query\SearchHotels;

final class SearchHotelsResult
{
    /** @param HotelListItem[] $items */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {
    }
}