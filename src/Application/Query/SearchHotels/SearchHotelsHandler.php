<?php

declare(strict_types=1);

namespace App\Application\Query\SearchHotels;

use App\Application\Bus\QueryHandlerInterface;
use App\Application\Bus\QueryInterface;

final class SearchHotelsHandler implements QueryHandlerInterface
{
    public function __construct(private readonly HotelSearchRepositoryInterface $repository)
    {
    }

    public function handle(QueryInterface $query): SearchHotelsResult
    {
        assert($query instanceof SearchHotelsQuery);

        return $this->repository->search($query->criteria);
    }
}