<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Pdo;

use App\Domain\Entity\Hotel;
use App\Domain\Enum\HotelStatus;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\HotelRepositoryInterface;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\GeoPoint;
use App\Domain\ValueObject\HotelId;
use App\Domain\ValueObject\HotelName;
use App\Domain\ValueObject\StarRating;
use App\Domain\ValueObject\UserId;

final class PdoHotelRepository implements HotelRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(Hotel $hotel): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO hotels
                (id, name, description, street, city, country, postal_code,
                 latitude, longitude, stars, manager_id, status, created_at)
            VALUES
                (:id, :name, :description, :street, :city, :country, :postal_code,
                 :latitude, :longitude, :stars, :manager_id, :status, :created_at)
            ON CONFLICT (id) DO UPDATE SET
                name        = EXCLUDED.name,
                description = EXCLUDED.description,
                street      = EXCLUDED.street,
                city        = EXCLUDED.city,
                country     = EXCLUDED.country,
                postal_code = EXCLUDED.postal_code,
                latitude    = EXCLUDED.latitude,
                longitude   = EXCLUDED.longitude,
                stars       = EXCLUDED.stars,
                manager_id  = EXCLUDED.manager_id,
                status      = EXCLUDED.status
        ');

        assert($stmt instanceof \PDOStatement);

        $stmt->execute([
            ':id'          => $hotel->getId()->value,
            ':name'        => $hotel->getName()->value,
            ':description' => $hotel->getDescription(),
            ':street'      => $hotel->getAddress()->street,
            ':city'        => $hotel->getAddress()->city,
            ':country'     => $hotel->getAddress()->country,
            ':postal_code' => $hotel->getAddress()->postalCode,
            ':latitude'    => $hotel->getLocation()->latitude,
            ':longitude'   => $hotel->getLocation()->longitude,
            ':stars'       => $hotel->getStarRating()->value,
            ':manager_id'  => $hotel->getManagerId()->value,
            ':status'      => $hotel->getStatus()->value,
            ':created_at'  => $hotel->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }

    public function findById(HotelId $id): Hotel
    {
        $stmt = $this->pdo->prepare('SELECT * FROM hotels WHERE id = :id');
        assert($stmt instanceof \PDOStatement);
        $stmt->execute([':id' => $id->value]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            throw new EntityNotFoundException("Hotel with id '{$id->value}' not found");
        }

        return $this->hydrate($row);
    }

    /** @return Hotel[] */
    public function findAll(): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM hotels ORDER BY created_at DESC');
        assert($stmt instanceof \PDOStatement);
        $stmt->execute();

        /** @var list<array<string, mixed>> $rows */
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(fn (array $row) => $this->hydrate($row), $rows);
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): Hotel
    {
        return Hotel::reconstitute(
            new HotelId((string) $row['id']),
            new HotelName((string) $row['name']),
            (string) $row['description'],
            new Address(
                (string) $row['street'],
                (string) $row['city'],
                (string) $row['country'],
                (string) $row['postal_code'],
            ),
            new GeoPoint((float) $row['latitude'], (float) $row['longitude']),
            new StarRating((int) $row['stars']),
            new UserId((string) $row['manager_id']),
            HotelStatus::from((string) $row['status']),
            new \DateTimeImmutable((string) $row['created_at']),
        );
    }
}
