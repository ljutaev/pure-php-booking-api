<?php

declare(strict_types=1);

namespace App\Application\Command\CreateHotel;

use App\Application\Bus\CommandInterface;

final class CreateHotelCommand implements CommandInterface
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $street,
        public readonly string $city,
        public readonly string $country,
        public readonly string $postalCode,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly int $stars,
        public readonly string $managerId,
    ) {
    }
}
