<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Command;

use App\Application\Auth\TokenPair;
use App\Application\Auth\TokenServiceInterface;
use App\Application\Command\RefreshToken\RefreshTokenCommand;
use App\Application\Command\RefreshToken\RefreshTokenHandler;
use App\Application\Port\RefreshTokenStorageInterface;
use App\Application\Port\TokenBlacklistInterface;
use App\Domain\Entity\User;
use App\Domain\Exception\InvalidTokenException;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

class RefreshTokenHandlerTest extends TestCase
{
    private User $user;
    private string $userId;

    protected function setUp(): void
    {
        $this->userId = 'f47ac10b-58cc-4372-a567-0e02b2c3d479';
        $this->user   = new User(
            new UserId($this->userId),
            new Email('user@example.com'),
            'hash',
            'Jane',
            'Doe',
        );
    }

    private function makeHandler(
        ?UserRepositoryInterface $users = null,
        ?TokenServiceInterface $ts = null,
        ?RefreshTokenStorageInterface $storage = null,
        ?TokenBlacklistInterface $blacklist = null,
    ): RefreshTokenHandler {
        if ($users === null) {
            $users = $this->createMock(UserRepositoryInterface::class);
            $users->method('findById')->willReturn($this->user);
        }

        if ($ts === null) {
            $ts = $this->createMock(TokenServiceInterface::class);
            $ts->method('issueTokenPair')->willReturn(new TokenPair('new-access', 'new-refresh', 900));
        }

        return new RefreshTokenHandler(
            $users,
            $ts,
            $storage ?? $this->createMock(RefreshTokenStorageInterface::class),
            $blacklist ?? $this->createMock(TokenBlacklistInterface::class),
        );
    }

    public function testHandleReturnsNewTokenPair(): void
    {
        $storage = $this->createMock(RefreshTokenStorageInterface::class);
        $storage->method('exists')->willReturn(true);

        $result = $this->makeHandler(storage: $storage)
            ->handle(new RefreshTokenCommand($this->userId, 'old-jti', 'old-access-token'));

        self::assertInstanceOf(TokenPair::class, $result);
        self::assertSame('new-access', $result->accessToken);
    }

    public function testHandleRevokesOldRefreshToken(): void
    {
        $storage = $this->createMock(RefreshTokenStorageInterface::class);
        $storage->method('exists')->willReturn(true);
        $storage->expects($this->once())->method('revoke')->with($this->userId, 'old-jti');

        $this->makeHandler(storage: $storage)
            ->handle(new RefreshTokenCommand($this->userId, 'old-jti', 'old-access-token'));
    }

    public function testHandleStoresNewRefreshToken(): void
    {
        $storage = $this->createMock(RefreshTokenStorageInterface::class);
        $storage->method('exists')->willReturn(true);
        $storage->expects($this->once())->method('store')
            ->with($this->userId, 'new-refresh', $this->greaterThan(0));

        $this->makeHandler(storage: $storage)
            ->handle(new RefreshTokenCommand($this->userId, 'old-jti', 'old-access-token'));
    }

    public function testHandleBlacklistsOldAccessToken(): void
    {
        $storage = $this->createMock(RefreshTokenStorageInterface::class);
        $storage->method('exists')->willReturn(true);

        $ts = $this->createMock(TokenServiceInterface::class);
        $ts->method('issueTokenPair')->willReturn(new TokenPair('new-access', 'new-refresh', 900));
        $ts->method('verifyAccessToken')->willReturn(['jti' => 'old-jti', 'exp' => time() + 900]);

        $blacklist = $this->createMock(TokenBlacklistInterface::class);
        $blacklist->expects($this->once())->method('add')->with('old-jti', $this->greaterThan(0));

        $this->makeHandler(ts: $ts, storage: $storage, blacklist: $blacklist)
            ->handle(new RefreshTokenCommand($this->userId, 'old-jti', 'old-access-token'));
    }

    public function testHandleThrowsWhenRefreshTokenNotFound(): void
    {
        $storage = $this->createMock(RefreshTokenStorageInterface::class);
        $storage->method('exists')->willReturn(false);

        $this->expectException(InvalidTokenException::class);
        $this->makeHandler(storage: $storage)
            ->handle(new RefreshTokenCommand($this->userId, 'unknown-jti', 'any-access-token'));
    }
}
