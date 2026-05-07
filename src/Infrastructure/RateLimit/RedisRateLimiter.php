<?php

declare(strict_types=1);

namespace App\Infrastructure\RateLimit;

use App\Application\Port\RateLimiterInterface;

final class RedisRateLimiter implements RateLimiterInterface
{
    public function __construct(
        private readonly \Redis $redis,
        private readonly int $maxRequests,
        private readonly int $windowSeconds,
    ) {
    }

    public function isAllowed(string $key): bool
    {
        $current = $this->redis->incr($key);

        if ($current === 1) {
            $this->redis->expire($key, $this->windowSeconds);
        }

        return $current <= $this->maxRequests;
    }
}