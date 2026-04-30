<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Room;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\GuestCount;
use App\Domain\ValueObject\HotelId;
use App\Domain\ValueObject\RoomId;

interface RoomRepositoryInterface
{
    public function save(Room $room): void;

    public function findById(RoomId $id): Room;

    /** @return Room[] */
    public function findByHotelId(HotelId $hotelId): array;

    /** @return Room[] */
    public function findAvailableRooms(HotelId $hotelId, DateRange $dateRange, GuestCount $guests): array;
}
