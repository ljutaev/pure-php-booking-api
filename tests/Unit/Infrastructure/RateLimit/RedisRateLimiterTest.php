<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\RateLimit;

use App\Infrastructure\RateLimit\RedisRateLimiter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RedisRateLimiterTest extends TestCase
{
    /** @var \Redis&MockObject */
    private \Redis $redis;

    protected function setUp(): void
    {
        $this->redis = $this->createMock(\Redis::class);
    }

    public function testAllowsRequestWhenUnderLimit(): void
    {
        $this->redis->method('incr')->willReturn(1);
        $this->redis->expects(self::once())->method('expire');

        $limiter = new RedisRateLimiter($this->redis, maxRequests: 60, windowSeconds: 60);

        self::assertTrue($limiter->isAllowed('rate:ip:127.0.0.1:/api'));
    }

    public function testAllowsRequestAtExactLimit(): void
    {
        $this->redis->method('incr')->willReturn(60);

        $limiter = new RedisRateLimiter($this->redis, maxRequests: 60, windowSeconds: 60);

        self::assertTrue($limiter->isAllowed('rate:ip:127.0.0.1:/api'));
    }

    public function testBlocksRequestOverLimit(): void
    {
        $this->redis->method('incr')->willReturn(61);

        $limiter = new RedisRateLimiter($this->redis, maxRequests: 60, windowSeconds: 60);

        self::assertFalse($limiter->isAllowed('rate:ip:127.0.0.1:/api'));
    }

    public function testSetsExpiryOnlyOnFirstRequest(): void
    {
        $this->redis->method('incr')->willReturn(5);
        $this->redis->expects(self::never())->method('expire');

        $limiter = new RedisRateLimiter($this->redis, maxRequests: 60, windowSeconds: 60);
        $limiter->isAllowed('rate:ip:127.0.0.1:/api');
    }
}
