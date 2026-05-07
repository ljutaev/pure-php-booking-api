<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Lock;

use App\Infrastructure\Lock\RedisBookingLock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RedisBookingLockTest extends TestCase
{
    /** @var \Redis&MockObject */
    private \Redis $redis;
    private RedisBookingLock $lock;

    protected function setUp(): void
    {
        $this->redis = $this->createMock(\Redis::class);
        $this->lock  = new RedisBookingLock($this->redis);
    }

    public function testAcquireReturnsTrueWhenKeyDidNotExist(): void
    {
        $this->redis->method('set')->willReturn(true);

        self::assertTrue($this->lock->acquire('room-1', '2026-06-01', '2026-06-05'));
    }

    public function testAcquireReturnsFalseWhenKeyAlreadyExists(): void
    {
        $this->redis->method('set')->willReturn(false);

        self::assertFalse($this->lock->acquire('room-1', '2026-06-01', '2026-06-05'));
    }

    public function testAcquireUsesNxOptionToPreventOverwrite(): void
    {
        $this->redis->expects(self::once())->method('set')
            ->with(
                'booking:lock:room-1:2026-06-01:2026-06-05',
                '1',
                self::callback(fn (array $opts): bool => in_array('nx', $opts, true) && $opts['ex'] === 300),
            )
            ->willReturn(true);

        $this->lock->acquire('room-1', '2026-06-01', '2026-06-05');
    }

    public function testReleaseDeletesTheLockKey(): void
    {
        $this->redis->expects(self::once())->method('del')
            ->with('booking:lock:room-1:2026-06-01:2026-06-05');

        $this->lock->release('room-1', '2026-06-01', '2026-06-05');
    }
}
