<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Redis;

use App\Application\Query\SearchHotels\HotelListItem;
use App\Application\Query\SearchHotels\HotelSearchRepositoryInterface;
use App\Application\Query\SearchHotels\SearchHotelsResult;
use App\Application\Search\HotelSearchCriteria;

final class RedisHotelSearchRepository implements HotelSearchRepositoryInterface
{
    private const TTL = 120; // 2 minutes

    public function __construct(
        private readonly HotelSearchRepositoryInterface $inner,
        private readonly \Redis $redis,
    ) {
    }

    public function search(HotelSearchCriteria $criteria): SearchHotelsResult
    {
        $key    = $this->cacheKey($criteria);
        $cached = $this->redis->get($key);

        if ($cached !== false && is_string($cached)) {
            return $this->deserialize($cached);
        }

        $result = $this->inner->search($criteria);
        $this->redis->setEx($key, self::TTL, $this->serialize($result));

        return $result;
    }

    private function cacheKey(HotelSearchCriteria $criteria): string
    {
        $hash = md5(serialize($criteria));

        return "cache:search:{$hash}";
    }

    private function serialize(SearchHotelsResult $result): string
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
        ], JSON_THROW_ON_ERROR);
    }

    private function deserialize(string $json): SearchHotelsResult
    {
        /** @var array{items: list<array<string, mixed>>, total: int, page: int, per_page: int} $data */
        $data  = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $items = array_map(static fn (array $row): HotelListItem => new HotelListItem(
            id: (string) $row['id'],
            name: (string) $row['name'],
            city: (string) $row['city'],
            country: (string) $row['country'],
            latitude: (float) $row['latitude'],
            longitude: (float) $row['longitude'],
            stars: (int) $row['stars'],
            distanceKm: $row['distance_km'] !== null ? (float) $row['distance_km'] : null,
            minPricePerNight: $row['min_price_per_night'] !== null ? (float) $row['min_price_per_night'] : null,
        ), $data['items']);

        return new SearchHotelsResult(
            items: $items,
            total: (int) $data['total'],
            page: (int) $data['page'],
            perPage: (int) $data['per_page'],
        );
    }
}