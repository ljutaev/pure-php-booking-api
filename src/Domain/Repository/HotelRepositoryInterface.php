<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Hotel;
use App\Domain\ValueObject\HotelId;

interface HotelRepositoryInterface
{
    public function save(Hotel $hotel): void;

    public function findById(HotelId $id): Hotel;

    /** @return Hotel[] */
    public function findAll(): array;
}
