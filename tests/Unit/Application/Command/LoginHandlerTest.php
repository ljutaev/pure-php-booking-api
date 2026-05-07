<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Command;

use App\Application\Auth\TokenPair;
use App\Application\Auth\TokenServiceInterface;
use App\Application\Command\Login\LoginCommand;
use App\Application\Command\Login\LoginHandler;
use App\Application\Port\RefreshTokenStorageInterface;
use App\Domain\Entity\User;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;

class LoginHandlerTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User(
            UserId::generate(),
            new Email('user@example.com'),
            password_hash('correct-password', PASSWORD_BCRYPT),
            'Jane',
            'Doe',
        );
    }

    private function makeTokenService(): TokenServiceInterface
    {
        $mock = $this->createMock(TokenServiceInterface::class);
        $mock->method('issueTokenPair')->willReturn(
            new TokenPair('access-token', 'refresh-uuid', 900),
        );

        return $mock;
    }

    public function testHandleReturnsTokenPair(): void
    {
        $users = $this->createMock(\App\Domain\Repository\UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn($this->user);

        $storage = $this->createMock(RefreshTokenStorageInterface::class);
        $result  = (new LoginHandler($users, $this->makeTokenService(), $storage))
            ->handle(new LoginCommand('user@example.com', 'correct-password'));

        self::assertInstanceOf(TokenPair::class, $result);
        self::assertSame('access-token', $result->accessToken);
    }

    public function testHandleStoresRefreshToken(): void
    {
        $users = $this->createMock(\App\Domain\Repository\UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn($this->user);

        $storage = $this->createMock(RefreshTokenStorageInterface::class);
        $storage->expects($this->once())
            ->method('store')
            ->with(
                $this->user->getId()->value,
                'refresh-uuid',
                $this->greaterThan(0),
            );

        (new LoginHandler($users, $this->makeTokenService(), $storage))
            ->handle(new LoginCommand('user@example.com', 'correct-password'));
    }

    public function testHandleThrowsWhenEmailNotFound(): void
    {
        $users = $this->createMock(\App\Domain\Repository\UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn(null);

        $this->expectException(EntityNotFoundException::class);
        (new LoginHandler($users, $this->makeTokenService(), $this->createMock(RefreshTokenStorageInterface::class)))
            ->handle(new LoginCommand('unknown@example.com', 'pass'));
    }

    public function testHandleThrowsWhenPasswordWrong(): void
    {
        $users = $this->createMock(\App\Domain\Repository\UserRepositoryInterface::class);
        $users->method('findByEmail')->willReturn($this->user);

        $this->expectException(\App\Domain\Exception\InvalidCredentialsException::class);
        (new LoginHandler($users, $this->makeTokenService(), $this->createMock(RefreshTokenStorageInterface::class)))
            ->handle(new LoginCommand('user@example.com', 'wrong-password'));
    }
}
