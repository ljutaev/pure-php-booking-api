<?php

declare(strict_types=1);

namespace App\Application\Command\RefreshToken;

use App\Application\Bus\CommandInterface;

final class RefreshTokenCommand implements CommandInterface
{
    public function __construct(
        public readonly string $userId,
        public readonly string $refreshToken,
        public readonly string $accessToken,
    ) {
    }
}
