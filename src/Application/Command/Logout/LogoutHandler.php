<?php

declare(strict_types=1);

namespace App\Application\Command\Logout;

use App\Application\Auth\TokenServiceInterface;
use App\Application\Bus\CommandHandlerInterface;
use App\Application\Bus\CommandInterface;
use App\Application\Port\TokenBlacklistInterface;

final class LogoutHandler implements CommandHandlerInterface
{
    public function __construct(
        private readonly TokenServiceInterface $tokenService,
        private readonly TokenBlacklistInterface $blacklist,
    ) {
    }

    public function handle(CommandInterface $command): mixed
    {
        assert($command instanceof LogoutCommand);

        $claims = $this->tokenService->verifyAccessToken($command->accessToken);

        /** @var string $jti */
        $jti = $claims['jti'];
        /** @var int $exp */
        $exp = $claims['exp'];

        $ttl = max(0, $exp - time());
        $this->blacklist->add($jti, $ttl);

        return null;
    }
}
