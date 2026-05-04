<?php

declare(strict_types=1);

namespace App\Presentation\Http;

use App\Application\Auth\TokenServiceInterface;
use App\Application\Port\TokenBlacklistInterface;
use App\Domain\Exception\InvalidTokenException;

final class AuthMiddleware
{
    public function __construct(
        private readonly TokenServiceInterface $tokenService,
        private readonly TokenBlacklistInterface $blacklist,
    ) {
    }

    /** @param callable(Request): JsonResponse $next */
    public function process(Request $request, callable $next): JsonResponse
    {
        $auth = $request->getHeader('authorization');

        if ($auth === null || !str_starts_with($auth, 'Bearer ')) {
            return JsonResponse::unauthorized('Missing or invalid Authorization header');
        }

        $token = substr($auth, 7);

        try {
            $claims = $this->tokenService->verifyAccessToken($token);
        } catch (InvalidTokenException) {
            return JsonResponse::unauthorized('Token is invalid or expired');
        }

        /** @var string $jti */
        $jti = $claims['jti'] ?? '';

        if ($jti !== '' && $this->blacklist->contains($jti)) {
            return JsonResponse::unauthorized('Token has been revoked');
        }

        /** @var string $userId */
        $userId = $claims['sub'] ?? '';
        /** @var string $role */
        $role = $claims['role'] ?? '';

        return $next($request->withAuthClaims($userId, $role));
    }
}
