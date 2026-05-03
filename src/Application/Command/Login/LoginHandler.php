<?php

declare(strict_types=1);

namespace App\Application\Command\Login;

use App\Application\Auth\TokenPair;
use App\Application\Auth\TokenServiceInterface;
use App\Application\Bus\CommandHandlerInterface;
use App\Application\Bus\CommandInterface;
use App\Application\Port\RefreshTokenStorageInterface;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Exception\InvalidCredentialsException;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;

final class LoginHandler implements CommandHandlerInterface
{
    private const REFRESH_TOKEN_TTL = 30 * 24 * 60 * 60; // 30 days

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TokenServiceInterface $tokenService,
        private readonly RefreshTokenStorageInterface $refreshStorage,
    ) {
    }

    public function handle(CommandInterface $command): TokenPair
    {
        assert($command instanceof LoginCommand);

        $user = $this->users->findByEmail(new Email($command->email));

        if ($user === null) {
            throw new EntityNotFoundException('User not found');
        }

        if (!password_verify($command->password, $user->getPasswordHash())) {
            throw new InvalidCredentialsException('Invalid credentials');
        }

        $pair = $this->tokenService->issueTokenPair($user);

        $this->refreshStorage->store($user->getId()->value, $pair->refreshToken, self::REFRESH_TOKEN_TTL);

        return $pair;
    }
}
