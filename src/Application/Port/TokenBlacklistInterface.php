<?php

declare(strict_types=1);

namespace App\Application\Port;

interface TokenBlacklistInterface
{
    public function add(string $jti, int $ttl): void;

    public function contains(string $jti): bool;
}
