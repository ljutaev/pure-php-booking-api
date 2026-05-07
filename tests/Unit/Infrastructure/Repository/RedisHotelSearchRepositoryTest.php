<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repository;

use App\Application\Query\SearchHotels\HotelListItem;
use App\Application\Query\SearchHotels\HotelSearchRepositoryInterface;
use App\Application\Query\SearchHotels\SearchHotelsResult;
use App\Application\Search\HotelSearchCriteria;
use App\Infrastructure\Repository\Redis\RedisHotelSearchRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RedisHotelSearchRepositoryTest extends TestCase
{
    /** @var HotelSearchRepositoryInterface&MockObject */
    private HotelSearchRepositoryInterface $inner;
    /** @var \Redis&MockObject */
    private \Redis $redis;
    private RedisHotelSearchRepository $repository;

    protected function setUp(): void
    {
        $this->inner      = $this->createMock(HotelSearchRepositoryInterface::class);
        $this->redis      = $this->createMock(\Redis::class);
        $this->repository = new RedisHotelSearchRepository($this->inner, $this->redis);
    }

    public function testSearchReturnsCachedResultWithoutHittingDb(): void
    {
        $result = $this->makeResult();
        $json   = $this->serializeResult($result);

        $this->redis->method('get')->willReturn($json);
        $this->inner->expects(self::never())->method('search');

        $cached = $this->repository->search(new HotelSearchCriteria(stars: 4));

        self::assertSame(1, $cached->total);
        self::assertSame('Grand Hotel', $cached->items[0]->name);
    }

    public function testSearchFetchesFromDbOnCacheMissAndCaches(): void
    {
        $result = $this->makeResult();

        $this->redis->method('get')->willReturn(false);
        $this->inner->expects(self::once())->method('search')->willReturn($result);
        $this->redis->expects(self::once())->method('setEx')
            ->with(self::stringStartsWith('cache:search:'), 120, self::isString());

        $fresh = $this->repository->search(new HotelSearchCriteria(stars: 4));

        self::assertSame(1, $fresh->total);
    }

    public function testDifferentCriteriaProduceDifferentCacheKeys(): void
    {
        $keys = [];

        $this->redis->method('get')->willReturn(false);
        $this->redis->method('setEx')
            ->willReturnCallback(function (string $key) use (&$keys): void {
                $keys[] = $key;
            });
        $this->inner->method('search')->willReturn($this->makeResult());

        $this->repository->search(new HotelSearchCriteria(stars: 3));
        $this->repository->search(new HotelSearchCriteria(stars: 5));

        self::assertCount(2, array_unique($keys));
    }

    private function makeResult(): SearchHotelsResult
    {
        $item = new HotelListItem(
            id: 'uuid-1',
            name: 'Grand Hotel',
            city: 'Kyiv',
            country: 'UA',
            latitude: 50.45,
            longitude: 30.52,
            stars: 5,
            distanceKm: null,
            minPricePerNight: 120.0,
        );

        return new SearchHotelsResult(items: [$item], total: 1, page: 1, perPage: 20);
    }

    private function serializeResult(SearchHotelsResult $result): string
    {
        $items = array_map(static fn (HotelListItem $item): array => [
            'id'                  => $item->id,
            'name'                => $item->name,
            'city'                => $item->city,
            'country'             => $item->country,
            'latitude'            => $item->latitude,
            'longitude'           => $item->longitude,
            'stars'               => $item->stars,
            'distance_km'         => $item->distanceKm,
            'min_price_per_night' => $item->minPricePerNight,
        ], $result->items);

        return (string) json_encode([
            'items'    => $items,
            'total'    => $result->total,
            'page'     => $result->page,
            'per_page' => $result->perPage,
        ]);
    }
}