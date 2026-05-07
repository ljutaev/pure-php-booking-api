<?php

declare(strict_types=1);

namespace Tests\Unit\Presentation\Http;

use App\Application\Port\RateLimiterInterface;
use App\Presentation\Http\JsonResponse;
use App\Presentation\Http\RateLimitMiddleware;
use App\Presentation\Http\Request;
use PHPUnit\Framework\TestCase;

final class RateLimitMiddlewareTest extends TestCase
{
    public function testPassesRequestWhenAllowed(): void
    {
        $limiter = $this->createMock(RateLimiterInterface::class);
        $limiter->method('isAllowed')->willReturn(true);

        $middleware = new RateLimitMiddleware($limiter);
        $request    = Request::create('GET', '/api/v1/hotels');

        $response = $middleware->process($request, fn () => JsonResponse::ok(['data' => []]));

        self::assertSame(200, $response->statusCode);
    }

    public function testReturns429WhenRateLimitExceeded(): void
    {
        $limiter = $this->createMock(RateLimiterInterface::class);
        $limiter->method('isAllowed')->willReturn(false);

        $middleware = new RateLimitMiddleware($limiter);
        $request    = Request::create('GET', '/api/v1/hotels');

        $response = $middleware->process($request, fn () => JsonResponse::ok([]));

        self::assertSame(429, $response->statusCode);
        self::assertSame('TOO_MANY_REQUESTS', $response->data['error']['code']);
    }

    public function testKeyIncludesEndpointPath(): void
    {
        $capturedKey = '';

        $limiter = $this->createMock(RateLimiterInterface::class);
        $limiter->method('isAllowed')
            ->willReturnCallback(function (string $key) use (&$capturedKey): bool {
                $capturedKey = $key;

                return true;
            });

        $middleware = new RateLimitMiddleware($limiter);
        $middleware->process(Request::create('GET', '/api/v1/hotels'), fn () => JsonResponse::ok([]));

        self::assertStringContainsString('/api/v1/hotels', $capturedKey);
        self::assertStringStartsWith('rate:ip:', $capturedKey);
    }
}
