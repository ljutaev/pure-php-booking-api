<?php

declare(strict_types=1);

namespace App\Application\Command\Login;

use App\Application\Bus\CommandInterface;

final class LoginCommand implements CommandInterface
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {
    }
}
