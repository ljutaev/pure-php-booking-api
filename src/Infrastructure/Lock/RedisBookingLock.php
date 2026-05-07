<?php

declare(strict_types=1);

namespace App\Infrastructure\Lock;

use App\Application\Port\BookingLockInterface;

final class RedisBookingLock implements BookingLockInterface
{
    private const TTL = 300; // 5 minutes

    public function __construct(private readonly \Redis $redis)
    {
    }

    public function acquire(string $roomId, string $checkIn, string $checkOut): bool
    {
        $key = $this->key($roomId, $checkIn, $checkOut);

        return (bool) $this->redis->set($key, '1', ['nx', 'ex' => self::TTL]);
    }

    public function release(string $roomId, string $checkIn, string $checkOut): void
    {
        $this->redis->del($this->key($roomId, $checkIn, $checkOut));
    }

    private function key(string $roomId, string $checkIn, string $checkOut): string
    {
        return "booking:lock:{$roomId}:{$checkIn}:{$checkOut}";
    }
}
