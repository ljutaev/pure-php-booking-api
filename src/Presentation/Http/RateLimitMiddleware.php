<?php

declare(strict_types=1);

namespace App\Presentation\Http;

use App\Application\Port\RateLimiterInterface;

final class RateLimitMiddleware
{
    public function __construct(private readonly RateLimiterInterface $limiter)
    {
    }

    public function process(Request $request, callable $next): JsonResponse
    {
        $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $endpoint = preg_replace('/[^a-z0-9_\/]/', '_', strtolower($request->uri)) ?? 'unknown';
        $key      = "rate:ip:{$ip}:{$endpoint}";

        if (!$this->limiter->isAllowed($key)) {
            return JsonResponse::tooManyRequests();
        }

        return $next($request);
    }
}