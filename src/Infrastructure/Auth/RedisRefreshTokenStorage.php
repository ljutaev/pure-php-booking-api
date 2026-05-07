<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Application\Port\RefreshTokenStorageInterface;

final class RedisRefreshTokenStorage implements RefreshTokenStorageInterface
{
    public function __construct(private readonly \Redis $redis)
    {
    }

    public function store(string $userId, string $jti, int $ttl): void
    {
        $this->redis->setEx($this->key($userId, $jti), $ttl, '1');
    }

    public function exists(string $userId, string $jti): bool
    {
        return (bool) $this->redis->exists($this->key($userId, $jti));
    }

    public function revoke(string $userId, string $jti): void
    {
        $this->redis->del($this->key($userId, $jti));
    }

    private function key(string $userId, string $jti): string
    {
        return "auth:refresh:{$userId}:{$jti}";
    }
}
