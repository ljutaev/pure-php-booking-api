<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Redis;

use App\Domain\Entity\Hotel;
use App\Domain\Enum\HotelStatus;
use App\Domain\Repository\HotelRepositoryInterface;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\GeoPoint;
use App\Domain\ValueObject\HotelId;
use App\Domain\ValueObject\HotelName;
use App\Domain\ValueObject\StarRating;
use App\Domain\ValueObject\UserId;

final class RedisHotelRepository implements HotelRepositoryInterface
{
    private const TTL = 600; // 10 minutes

    public function __construct(
        private readonly HotelRepositoryInterface $inner,
        private readonly \Redis $redis,
    ) {
    }

    public function save(Hotel $hotel): void
    {
        $this->inner->save($hotel);
        $this->redis->del("cache:hotel:{$hotel->getId()->value}");
    }

    public function findById(HotelId $id): Hotel
    {
        $key    = "cache:hotel:{$id->value}";
        $cached = $this->redis->get($key);

        if ($cached !== false && is_string($cached)) {
            return $this->deserialize($cached);
        }

        $hotel = $this->inner->findById($id);
        $this->redis->setEx($key, self::TTL, $this->serialize($hotel));

        return $hotel;
    }

    /** @return Hotel[] */
    public function findAll(): array
    {
        return $this->inner->findAll();
    }

    private function serialize(Hotel $hotel): string
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
        ], JSON_THROW_ON_ERROR);
    }

    private function deserialize(string $json): Hotel
    {
        /** @var array<string, mixed> $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return Hotel::reconstitute(
            new HotelId((string) $data['id']),
            new HotelName((string) $data['name']),
            (string) $data['description'],
            new Address(
                (string) $data['street'],
                (string) $data['city'],
                (string) $data['country'],
                (string) $data['postal_code'],
            ),
            new GeoPoint((float) $data['latitude'], (float) $data['longitude']),
            new StarRating((int) $data['stars']),
            new UserId((string) $data['manager_id']),
            HotelStatus::from((string) $data['status']),
            new \DateTimeImmutable((string) $data['created_at']),
        );
    }
}