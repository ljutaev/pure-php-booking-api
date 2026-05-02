<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Domain\Entity\Hotel;
use App\Domain\Enum\HotelStatus;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\GeoPoint;
use App\Domain\ValueObject\HotelId;
use App\Domain\ValueObject\HotelName;
use App\Domain\ValueObject\StarRating;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Repository\Pdo\PdoHotelRepository;
use Tests\Integration\IntegrationTestCase;

final class PdoHotelRepositoryTest extends IntegrationTestCase
{
    private PdoHotelRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PdoHotelRepository($this->pdo);
    }

    public function testSaveAndFindById(): void
    {
        $hotel = $this->makeHotel();
        $this->repository->save($hotel);

        $found = $this->repository->findById($hotel->getId());

        $this->assertTrue($hotel->getId()->equals($found->getId()));
        $this->assertSame('Grand Hotel', $found->getName()->value);
        $this->assertSame('A luxury hotel', $found->getDescription());
        $this->assertSame('active', $found->getStatus()->value);
        $this->assertSame(5, $found->getStarRating()->value);
        $this->assertSame(48.8566, $found->getLocation()->latitude);
        $this->assertSame(2.3522, $found->getLocation()->longitude);
    }

    public function testSaveUpdatesExistingHotel(): void
    {
        $hotel = $this->makeHotel();
        $this->repository->save($hotel);

        $hotel->deactivate();
        $this->repository->save($hotel);

        $found = $this->repository->findById($hotel->getId());
        $this->assertSame(HotelStatus::Inactive, $found->getStatus());
    }

    public function testFindByIdThrowsWhenNotFound(): void
    {
        $this->expectException(EntityNotFoundException::class);

        $this->repository->findById(HotelId::generate());
    }

    public function testFindAllReturnsAllHotels(): void
    {
        $hotel1 = $this->makeHotel('First Hotel');
        $hotel2 = $this->makeHotel('Second Hotel');

        $this->repository->save($hotel1);
        $this->repository->save($hotel2);

        $all = $this->repository->findAll();

        $ids = array_map(fn (Hotel $h) => $h->getId()->value, $all);
        $this->assertContains($hotel1->getId()->value, $ids);
        $this->assertContains($hotel2->getId()->value, $ids);
    }

    public function testFindAllReturnsEmptyArrayWhenNone(): void
    {
        $this->assertSame([], $this->repository->findAll());
    }

    private function makeHotel(string $name = 'Grand Hotel'): Hotel
    {
        return new Hotel(
            HotelId::generate(),
            new HotelName($name),
            'A luxury hotel',
            new Address('1 Rue de Rivoli', 'Paris', 'France', '75001'),
            new GeoPoint(48.8566, 2.3522),
            new StarRating(5),
            UserId::generate(),
        );
    }
}
