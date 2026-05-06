<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Query;

use App\Application\Query\SearchHotels\HotelListItem;
use App\Application\Query\SearchHotels\HotelSearchRepositoryInterface;
use App\Application\Query\SearchHotels\SearchHotelsHandler;
use App\Application\Query\SearchHotels\SearchHotelsQuery;
use App\Application\Query\SearchHotels\SearchHotelsResult;
use App\Application\Search\HotelSearchCriteria;
use PHPUnit\Framework\TestCase;

class SearchHotelsHandlerTest extends TestCase
{
    public function testHandleDelegatesToRepository(): void
    {
        $criteria = new HotelSearchCriteria(stars: 4);
        $expected = new SearchHotelsResult(items: [], total: 0, page: 1, perPage: 20);

        $repo = $this->createMock(HotelSearchRepositoryInterface::class);
        $repo->expects(self::once())
            ->method('search')
            ->with($criteria)
            ->willReturn($expected);

        $handler = new SearchHotelsHandler($repo);
        $result  = $handler->handle(new SearchHotelsQuery($criteria));

        self::assertSame($expected, $result);
    }

    public function testHandleReturnsSearchHotelsResult(): void
    {
        $item = new HotelListItem(
            id: 'uuid-1',
            name: 'Grand Hotel',
            city: 'Kyiv',
            country: 'UA',
            latitude: 50.45,
            longitude: 30.52,
            stars: 5,
            distanceKm: 2.3,
            minPricePerNight: 120.0,
        );
        $expected = new SearchHotelsResult(items: [$item], total: 1, page: 1, perPage: 20);

        $repo = $this->createMock(HotelSearchRepositoryInterface::class);
        $repo->method('search')->willReturn($expected);

        $handler = new SearchHotelsHandler($repo);
        $result  = $handler->handle(new SearchHotelsQuery(new HotelSearchCriteria()));

        self::assertCount(1, $result->items);
        self::assertSame(1, $result->total);
        self::assertSame('Grand Hotel', $result->items[0]->name);
    }
}