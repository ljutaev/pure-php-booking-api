<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Command;

use App\Application\Auth\TokenServiceInterface;
use App\Application\Command\Logout\LogoutCommand;
use App\Application\Command\Logout\LogoutHandler;
use App\Application\Port\TokenBlacklistInterface;
use PHPUnit\Framework\TestCase;

class LogoutHandlerTest extends TestCase
{
    public function testHandleBlacklistsToken(): void
    {
        $claims = ['sub' => 'user-id', 'jti' => 'some-jti', 'exp' => time() + 900];

        $tokenService = $this->createMock(TokenServiceInterface::class);
        $tokenService->method('verifyAccessToken')->willReturn($claims);

        $blacklist = $this->createMock(TokenBlacklistInterface::class);
        $blacklist->expects($this->once())
            ->method('add')
            ->with('some-jti', $this->greaterThan(0));

        (new LogoutHandler($tokenService, $blacklist))
            ->handle(new LogoutCommand('valid-access-token'));
    }

    public function testHandleThrowsOnInvalidToken(): void
    {
        $tokenService = $this->createMock(TokenServiceInterface::class);
        $tokenService->method('verifyAccessToken')
            ->willThrowException(new \App\Domain\Exception\InvalidTokenException('bad'));

        $blacklist = $this->createMock(TokenBlacklistInterface::class);

        $this->expectException(\App\Domain\Exception\InvalidTokenException::class);
        (new LogoutHandler($tokenService, $blacklist))
            ->handle(new LogoutCommand('bad-token'));
    }
}
