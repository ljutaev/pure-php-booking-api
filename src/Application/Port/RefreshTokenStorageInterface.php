<?php

declare(strict_types=1);

namespace App\Application\Port;

interface RefreshTokenStorageInterface
{
    public function store(string $userId, string $jti, int $ttl): void;

    public function exists(string $userId, string $jti): bool;

    public function revoke(string $userId, string $jti): void;
}
