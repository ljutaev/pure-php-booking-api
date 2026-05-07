<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Application\Port\TokenBlacklistInterface;

final class RedisTokenBlacklist implements TokenBlacklistInterface
{
    public function __construct(private readonly \Redis $redis)
    {
    }

    public function add(string $jti, int $ttl): void
    {
        if ($ttl > 0) {
            $this->redis->setEx("auth:blacklist:{$jti}", $ttl, '1');
        }
    }

    public function contains(string $jti): bool
    {
        return (bool) $this->redis->exists("auth:blacklist:{$jti}");
    }
}
