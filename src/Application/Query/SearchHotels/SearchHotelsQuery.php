<?php

declare(strict_types=1);

namespace App\Application\Query\SearchHotels;

use App\Application\Bus\QueryInterface;
use App\Application\Search\HotelSearchCriteria;

final class SearchHotelsQuery implements QueryInterface
{
    public function __construct(public readonly HotelSearchCriteria $criteria)
    {
    }
}