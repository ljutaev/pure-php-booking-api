<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Http;

use App\Application\Auth\TokenServiceInterface;
use App\Application\Port\TokenBlacklistInterface;
use App\Domain\Exception\InvalidTokenException;
use App\Presentation\Http\AuthMiddleware;
use App\Presentation\Http\JsonResponse;
use App\Presentation\Http\Request;
use PHPUnit\Framework\TestCase;

class AuthMiddlewareTest extends TestCase
{
    /** @param array<string, mixed> $claims */
    private function makeMiddleware(
        array $claims = ['sub' => 'user-id', 'role' => 'customer', 'jti' => 'jti-123'],
        bool $blacklisted = false,
    ): AuthMiddleware {
        $tokenService = $this->createMock(TokenServiceInterface::class);
        $tokenService->method('verifyAccessToken')->willReturn($claims);

        $blacklist = $this->createMock(TokenBlacklistInterface::class);
        $blacklist->method('contains')->willReturn($blacklisted);

        return new AuthMiddleware($tokenService, $blacklist);
    }

    public function testPassesRequestWithValidToken(): void
    {
        $middleware = $this->makeMiddleware();
        $request    = Request::create('GET', '/api/v1/hotels', []);
        $request    = $request->withHeader('Authorization', 'Bearer valid-token');

        $reached = false;
        $middleware->process($request, function (Request $r) use (&$reached): JsonResponse {
            $reached = true;

            return JsonResponse::ok(['ok' => true]);
        });

        self::assertTrue($reached);
    }

    public function testAttachesAuthClaimsToRequest(): void
    {
        $middleware = $this->makeMiddleware();
        $request    = Request::create('GET', '/api/v1/hotels', []);
        $request    = $request->withHeader('Authorization', 'Bearer valid-token');

        $capturedRequest = null;
        $middleware->process($request, function (Request $r) use (&$capturedRequest): JsonResponse {
            $capturedRequest = $r;

            return JsonResponse::ok([]);
        });

        self::assertNotNull($capturedRequest);
        self::assertSame('user-id', $capturedRequest->userId);
        self::assertSame('customer', $capturedRequest->userRole);
    }

    public function testReturns401WhenNoAuthHeader(): void
    {
        $middleware = $this->makeMiddleware();
        $request    = Request::create('GET', '/api/v1/hotels', []);

        $response = $middleware->process($request, fn () => JsonResponse::ok([]));

        self::assertSame(401, $response->statusCode);
    }

    public function testReturns401WhenTokenInvalid(): void
    {
        $tokenService = $this->createMock(TokenServiceInterface::class);
        $tokenService->method('verifyAccessToken')
            ->willThrowException(new InvalidTokenException('bad'));

        $blacklist   = $this->createMock(TokenBlacklistInterface::class);
        $middleware  = new AuthMiddleware($tokenService, $blacklist);
        $request     = Request::create('GET', '/api/v1/hotels', []);
        $request     = $request->withHeader('Authorization', 'Bearer bad-token');

        $response = $middleware->process($request, fn () => JsonResponse::ok([]));

        self::assertSame(401, $response->statusCode);
    }

    public function testReturns401WhenTokenBlacklisted(): void
    {
        $middleware = $this->makeMiddleware(blacklisted: true);
        $request    = Request::create('GET', '/api/v1/hotels', []);
        $request    = $request->withHeader('Authorization', 'Bearer blacklisted-token');

        $response = $middleware->process($request, fn () => JsonResponse::ok([]));

        self::assertSame(401, $response->statusCode);
    }
}
