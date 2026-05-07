<?php

declare(strict_types=1);

namespace App\Application\Command\RegisterUser;

use App\Application\Bus\CommandHandlerInterface;
use App\Application\Bus\CommandInterface;
use App\Domain\Entity\User;
use App\Domain\Exception\DuplicateEmailException;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\UserId;

final class RegisterUserHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {
    }

    public function handle(CommandInterface $command): UserId
    {
        assert($command instanceof RegisterUserCommand);

        $email = new Email($command->email);

        if ($this->users->findByEmail($email) !== null) {
            throw new DuplicateEmailException('Email is already registered');
        }

        $user = new User(
            UserId::generate(),
            $email,
            password_hash($command->password, PASSWORD_BCRYPT),
            $command->firstName,
            $command->lastName,
        );

        $this->users->save($user);

        return $user->getId();
    }
}
