<?php

declare(strict_types=1);

namespace App\Application\Query\SearchHotels;

use App\Application\Search\HotelSearchCriteria;

interface HotelSearchRepositoryInterface
{
    public function search(HotelSearchCriteria $criteria): SearchHotelsResult;
}