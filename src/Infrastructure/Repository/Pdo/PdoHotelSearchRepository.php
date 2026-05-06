<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Pdo;

use App\Application\Query\SearchHotels\HotelListItem;
use App\Application\Query\SearchHotels\HotelSearchRepositoryInterface;
use App\Application\Query\SearchHotels\SearchHotelsResult;
use App\Application\Search\HotelSearchCriteria;
use App\Infrastructure\Search\Specification\DateAvailabilitySpec;
use App\Infrastructure\Search\Specification\HotelSqlSpecificationInterface;
use App\Infrastructure\Search\Specification\LocationSpec;
use App\Infrastructure\Search\Specification\PriceRangeSpec;
use App\Infrastructure\Search\Specification\StarRatingSpec;

final class PdoHotelSearchRepository implements HotelSearchRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function search(HotelSearchCriteria $criteria): SearchHotelsResult
    {
        $specs  = $this->buildSpecs($criteria);
        $params = $this->collectParams($specs);

        $distanceExpr = $criteria->hasGeoFilter()
            ? '(6371 * acos(LEAST(1.0, cos(radians(:loc_lat)) * cos(radians(h.latitude))
                * cos(radians(h.longitude) - radians(:loc_lon))
                + sin(radians(:loc_lat)) * sin(radians(h.latitude))))) AS distance_km,'
            : 'NULL AS distance_km,';

        $where = $this->buildWhere($specs);

        $minPriceSub = '(SELECT MIN(r2.price_per_night) FROM rooms r2
                         WHERE r2.hotel_id = h.id AND r2.status = \'available\') AS min_price';

        $baseSql = "SELECT h.id, h.name, h.city, h.country,
                           h.latitude, h.longitude, h.stars,
                           {$distanceExpr}
                           {$minPriceSub}
                    FROM hotels h
                    {$where}
                    AND h.status = 'active'";

        $total = $this->countRows($baseSql, $params);

        $orderBy = $criteria->hasGeoFilter() ? 'ORDER BY distance_km ASC' : 'ORDER BY h.created_at DESC';

        $dataSql = "{$baseSql} {$orderBy} LIMIT :limit OFFSET :offset";
        $params[':limit']  = $criteria->perPage;
        $params[':offset'] = $criteria->offset();

        $stmt = $this->pdo->prepare($dataSql);
        assert($stmt instanceof \PDOStatement);
        $stmt->execute($params);

        /** @var list<array<string, mixed>> $rows */
        $rows  = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $items = array_map($this->hydrateItem(...), $rows);

        return new SearchHotelsResult(
            items: $items,
            total: $total,
            page: $criteria->page,
            perPage: $criteria->perPage,
        );
    }

    /** @return HotelSqlSpecificationInterface[] */
    private function buildSpecs(HotelSearchCriteria $criteria): array
    {
        $specs = [];

        if ($criteria->hasGeoFilter()) {
            $specs[] = new LocationSpec(
                (float) $criteria->latitude,
                (float) $criteria->longitude,
                (float) $criteria->radiusKm,
            );
        }

        if ($criteria->stars !== null) {
            $specs[] = new StarRatingSpec($criteria->stars);
        }

        if ($criteria->hasPriceFilter()) {
            $specs[] = new PriceRangeSpec($criteria->priceMin, $criteria->priceMax);
        }

        if ($criteria->hasDateFilter()) {
            $specs[] = new DateAvailabilitySpec(
                (string) $criteria->checkIn,
                (string) $criteria->checkOut,
            );
        }

        return $specs;
    }

    /** @param HotelSqlSpecificationInterface[] $specs
     *  @return array<string, mixed> */
    private function collectParams(array $specs): array
    {
        $params = [];

        foreach ($specs as $spec) {
            $params = array_merge($params, $spec->params());
        }

        return $params;
    }

    /** @param HotelSqlSpecificationInterface[] $specs */
    private function buildWhere(array $specs): string
    {
        if ($specs === []) {
            return 'WHERE 1=1';
        }

        $clauses = array_map(fn (HotelSqlSpecificationInterface $s) => $s->clause(), $specs);

        return 'WHERE ' . implode(' AND ', $clauses);
    }

    /** @param array<string, mixed> $params */
    private function countRows(string $baseSql, array $params): int
    {
        $countSql = "SELECT COUNT(*) FROM ({$baseSql}) sub";
        $stmt     = $this->pdo->prepare($countSql);
        assert($stmt instanceof \PDOStatement);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /** @param array<string, mixed> $row */
    private function hydrateItem(array $row): HotelListItem
    {
        return new HotelListItem(
            id: (string) $row['id'],
            name: (string) $row['name'],
            city: (string) $row['city'],
            country: (string) $row['country'],
            latitude: (float) $row['latitude'],
            longitude: (float) $row['longitude'],
            stars: (int) $row['stars'],
            distanceKm: $row['distance_km'] !== null ? (float) $row['distance_km'] : null,
            minPricePerNight: $row['min_price'] !== null ? (float) $row['min_price'] : null,
        );
    }
}