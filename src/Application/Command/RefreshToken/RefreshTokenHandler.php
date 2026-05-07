<?php

declare(strict_types=1);

namespace App\Application\Command\RefreshToken;

use App\Application\Auth\TokenPair;
use App\Application\Auth\TokenServiceInterface;
use App\Application\Bus\CommandHandlerInterface;
use App\Application\Bus\CommandInterface;
use App\Application\Port\RefreshTokenStorageInterface;
use App\Application\Port\TokenBlacklistInterface;
use App\Domain\Exception\InvalidTokenException;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\UserId;

final class RefreshTokenHandler implements CommandHandlerInterface
{
    private const REFRESH_TOKEN_TTL = 30 * 24 * 60 * 60;

    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TokenServiceInterface $tokenService,
        private readonly RefreshTokenStorageInterface $refreshStorage,
        private readonly TokenBlacklistInterface $blacklist,
    ) {
    }

    public function handle(CommandInterface $command): TokenPair
    {
        assert($command instanceof RefreshTokenCommand);

        if (!$this->refreshStorage->exists($command->userId, $command->refreshToken)) {
            throw new InvalidTokenException('Refresh token not found or already used');
        }

        $user = $this->users->findById(new UserId($command->userId));

        $this->refreshStorage->revoke($command->userId, $command->refreshToken);

        try {
            $claims = $this->tokenService->verifyAccessToken($command->accessToken);
            $jti    = isset($claims['jti']) && is_string($claims['jti']) ? $claims['jti'] : null;
            $exp    = isset($claims['exp']) && is_int($claims['exp']) ? $claims['exp'] : 0;

            if ($jti !== null) {
                $this->blacklist->add($jti, max(0, $exp - time()));
            }
        } catch (InvalidTokenException) {
            // Access token already expired — that's fine, we still rotate
        }

        $pair = $this->tokenService->issueTokenPair($user);

        $this->refreshStorage->store($command->userId, $pair->refreshToken, self::REFRESH_TOKEN_TTL);

        return $pair;
    }
}
