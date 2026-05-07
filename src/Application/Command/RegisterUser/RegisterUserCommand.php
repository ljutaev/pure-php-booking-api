<?php

declare(strict_types=1);

namespace App\Application\Command\RegisterUser;

use App\Application\Bus\CommandInterface;

final class RegisterUserCommand implements CommandInterface
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $firstName,
        public readonly string $lastName,
    ) {
    }
}
