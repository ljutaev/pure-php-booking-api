<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Repository;

use App\Domain\Entity\Hotel;
use App\Domain\Repository\HotelRepositoryInterface;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\GeoPoint;
use App\Domain\ValueObject\HotelId;
use App\Domain\ValueObject\HotelName;
use App\Domain\ValueObject\StarRating;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Repository\Redis\RedisHotelRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RedisHotelRepositoryTest extends TestCase
{
    /** @var HotelRepositoryInterface&MockObject */
    private HotelRepositoryInterface $inner;
    /** @var \Redis&MockObject */
    private \Redis $redis;
    private RedisHotelRepository $repository;

    protected function setUp(): void
    {
        $this->inner      = $this->createMock(HotelRepositoryInterface::class);
        $this->redis      = $this->createMock(\Redis::class);
        $this->repository = new RedisHotelRepository($this->inner, $this->redis);
    }

    public function testFindByIdReturnsCachedHotelWithoutHittingDb(): void
    {
        $hotel = $this->makeHotel();
        $json  = $this->serializeHotel($hotel);

        $this->redis->method('get')->willReturn($json);
        $this->inner->expects(self::never())->method('findById');

        $result = $this->repository->findById($hotel->getId());

        self::assertSame($hotel->getId()->value, $result->getId()->value);
    }

    public function testFindByIdFetchesFromDbOnCacheMissAndCachesResult(): void
    {
        $hotel = $this->makeHotel();

        $this->redis->method('get')->willReturn(false);
        $this->inner->expects(self::once())->method('findById')->willReturn($hotel);
        $this->redis->expects(self::once())->method('setEx')
            ->with("cache:hotel:{$hotel->getId()->value}", 600, self::isString());

        $result = $this->repository->findById($hotel->getId());

        self::assertSame($hotel->getId()->value, $result->getId()->value);
    }

    public function testSaveDelegatesToInnerAndInvalidatesCache(): void
    {
        $hotel = $this->makeHotel();

        $this->inner->expects(self::once())->method('save')->with($hotel);
        $this->redis->expects(self::once())->method('del')
            ->with("cache:hotel:{$hotel->getId()->value}");

        $this->repository->save($hotel);
    }

    public function testFindAllDelegatesToInner(): void
    {
        $hotel = $this->makeHotel();
        $this->inner->method('findAll')->willReturn([$hotel]);

        $result = $this->repository->findAll();

        self::assertCount(1, $result);
    }

    public function testDeserializedHotelHasCorrectFields(): void
    {
        $hotel = $this->makeHotel();
        $json  = $this->serializeHotel($hotel);

        $this->redis->method('get')->willReturn($json);

        $result = $this->repository->findById($hotel->getId());

        self::assertSame('Grand Hotel', $result->getName()->value);
        self::assertSame('Paris', $result->getAddress()->city);
        self::assertSame(5, $result->getStarRating()->value);
        self::assertSame('active', $result->getStatus()->value);
    }

    private function makeHotel(): Hotel
    {
        return new Hotel(
            HotelId::generate(),
            new HotelName('Grand Hotel'),
            'A luxury hotel',
            new Address('1 Rue de Rivoli', 'Paris', 'France', '75001'),
            new GeoPoint(48.8566, 2.3522),
            new StarRating(5),
            UserId::generate(),
        );
    }

    private function serializeHotel(Hotel $hotel): string
    {
        return (string) json_encode([
            'id'          => $hotel->getId()->value,
            'name'        => $hotel->getName()->value,
            'description' => $hotel->getDescription(),
            'street'      => $hotel->getAddress()->street,
            'city'        => $hotel->getAddress()->city,
            'country'     => $hotel->getAddress()->country,
            'postal_code' => $hotel->getAddress()->postalCode,
            'latitude'    => $hotel->getLocation()->latitude,
            'longitude'   => $hotel->getLocation()->longitude,
            'stars'       => $hotel->getStarRating()->value,
            'manager_id'  => $hotel->getManagerId()->value,
            'status'      => $hotel->getStatus()->value,
            'created_at'  => $hotel->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }
}