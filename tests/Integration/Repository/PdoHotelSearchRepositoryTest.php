<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Application\Search\HotelSearchCriteria;
use App\Domain\Entity\Hotel;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\GeoPoint;
use App\Domain\ValueObject\HotelId;
use App\Domain\ValueObject\HotelName;
use App\Domain\ValueObject\StarRating;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Repository\Pdo\PdoHotelRepository;
use App\Infrastructure\Repository\Pdo\PdoHotelSearchRepository;
use Tests\Integration\IntegrationTestCase;

final class PdoHotelSearchRepositoryTest extends IntegrationTestCase
{
    private PdoHotelSearchRepository $search;
    private PdoHotelRepository $hotels;

    protected function setUp(): void
    {
        parent::setUp();
        $this->search = new PdoHotelSearchRepository($this->pdo);
        $this->hotels = new PdoHotelRepository($this->pdo);
    }

    public function testSearchWithNoCriteriaReturnsActiveHotels(): void
    {
        $this->hotels->save($this->makeHotel('Hotel A', stars: 3, lat: 50.0, lon: 30.0));
        $this->hotels->save($this->makeHotel('Hotel B', stars: 4, lat: 50.1, lon: 30.1));

        $result = $this->search->search(new HotelSearchCriteria());

        $ids = array_map(fn ($item) => $item->name, $result->items);
        self::assertContains('Hotel A', $ids);
        self::assertContains('Hotel B', $ids);
        self::assertGreaterThanOrEqual(2, $result->total);
    }

    public function testSearchFiltersByStarRating(): void
    {
        $this->hotels->save($this->makeHotel('Budget Hotel', stars: 2, lat: 50.0, lon: 30.0));
        $this->hotels->save($this->makeHotel('Luxury Hotel', stars: 5, lat: 50.0, lon: 30.0));

        $result = $this->search->search(new HotelSearchCriteria(stars: 4));

        $names = array_map(fn ($item) => $item->name, $result->items);
        self::assertContains('Luxury Hotel', $names);
        self::assertNotContains('Budget Hotel', $names);
    }

    public function testSearchFiltersByGeoRadius(): void
    {
        // Kyiv center
        $this->hotels->save($this->makeHotel('Near Hotel', stars: 3, lat: 50.451, lon: 30.524));
        // Lviv — ~470 km away
        $this->hotels->save($this->makeHotel('Far Hotel', stars: 3, lat: 49.842, lon: 24.026));

        $result = $this->search->search(new HotelSearchCriteria(
            latitude: 50.45,
            longitude: 30.52,
            radiusKm: 10.0,
        ));

        $names = array_map(fn ($item) => $item->name, $result->items);
        self::assertContains('Near Hotel', $names);
        self::assertNotContains('Far Hotel', $names);
    }

    public function testSearchResultIsOrderedByDistanceWhenGeoFilterApplied(): void
    {
        $this->hotels->save($this->makeHotel('Close Hotel', stars: 3, lat: 50.451, lon: 30.522));
        $this->hotels->save($this->makeHotel('Mid Hotel', stars: 3, lat: 50.460, lon: 30.522));

        $result = $this->search->search(new HotelSearchCriteria(
            latitude: 50.45,
            longitude: 30.52,
            radiusKm: 20.0,
        ));

        self::assertNotNull($result->items[0]->distanceKm);
        // first result must be closer or equal to second
        if (count($result->items) >= 2) {
            self::assertLessThanOrEqual(
                (float) $result->items[1]->distanceKm,
                (float) $result->items[0]->distanceKm,
            );
        }
    }

    public function testSearchPaginatesResults(): void
    {
        $this->hotels->save($this->makeHotel('Paginate A', stars: 3, lat: 50.0, lon: 30.0));
        $this->hotels->save($this->makeHotel('Paginate B', stars: 3, lat: 50.0, lon: 30.0));
        $this->hotels->save($this->makeHotel('Paginate C', stars: 3, lat: 50.0, lon: 30.0));

        $page1 = $this->search->search(new HotelSearchCriteria(page: 1, perPage: 2));
        $page2 = $this->search->search(new HotelSearchCriteria(page: 2, perPage: 2));

        self::assertCount(2, $page1->items);
        self::assertGreaterThanOrEqual(1, count($page2->items));
        self::assertGreaterThanOrEqual(3, $page1->total);
        self::assertSame(1, $page1->page);
        self::assertSame(2, $page2->page);
    }

    private function makeHotel(
        string $name,
        int $stars = 3,
        float $lat = 50.0,
        float $lon = 30.0,
    ): Hotel {
        return new Hotel(
            HotelId::generate(),
            new HotelName($name),
            'Test hotel',
            new Address('1 Main St', 'Kyiv', 'UA', '01001'),
            new GeoPoint($lat, $lon),
            new StarRating($stars),
            UserId::generate(),
        );
    }
}
