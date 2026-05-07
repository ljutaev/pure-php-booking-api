<?php

declare(strict_types=1);

namespace App\Application\Port;

interface BookingLockInterface
{
    public function acquire(string $roomId, string $checkIn, string $checkOut): bool;

    public function release(string $roomId, string $checkIn, string $checkOut): void;
}
