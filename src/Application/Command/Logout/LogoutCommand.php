<?php

declare(strict_types=1);

namespace App\Application\Command\Logout;

use App\Application\Bus\CommandInterface;

final class LogoutCommand implements CommandInterface
{
    public function __construct(
        public readonly string $accessToken,
    ) {
    }
}
