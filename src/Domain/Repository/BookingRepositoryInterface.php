<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Booking;
use App\Domain\ValueObject\BookingId;
use App\Domain\ValueObject\DateRange;
use App\Domain\ValueObject\RoomId;

interface BookingRepositoryInterface
{
    public function save(Booking $booking): void;

    public function findById(BookingId $id): Booking;

    /** @return Booking[] */
    public function findByRoomAndDateRange(RoomId $roomId, DateRange $dateRange): array;
}
