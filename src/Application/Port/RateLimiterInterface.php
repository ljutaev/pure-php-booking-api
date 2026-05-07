<?php

declare(strict_types=1);

namespace App\Application\Port;

interface RateLimiterInterface
{
    public function isAllowed(string $key): bool;
}