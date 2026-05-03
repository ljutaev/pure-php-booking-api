<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Auth;

use App\Domain\Entity\User;
use App\Domain\Exception\InvalidTokenException;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\UserId;
use App\Infrastructure\Auth\JwtTokenService;
use PHPUnit\Framework\TestCase;

class JwtTokenServiceTest extends TestCase
{
    private JwtTokenService $service;
    private User $user;

    protected function setUp(): void
    {
        $this->service = new JwtTokenService('test-secret-key-at-least-32-chars!', 900);
        $this->user    = new User(
            UserId::generate(),
            new Email('test@example.com'),
            'hash',
            'Test',
            'User',
        );
    }

    public function testIssueTokenPairReturnsPair(): void
    {
        $pair = $this->service->issueTokenPair($this->user);

        self::assertNotEmpty($pair->accessToken);
        self::assertNotEmpty($pair->refreshToken);
        self::assertSame(900, $pair->expiresIn);
    }

    public function testAccessTokenContainsCorrectClaims(): void
    {
        $pair   = $this->service->issueTokenPair($this->user);
        $claims = $this->service->verifyAccessToken($pair->accessToken);

        self::assertSame($this->user->getId()->value, $claims['sub']);
        self::assertSame('test@example.com', $claims['email']);
        self::assertSame('customer', $claims['role']);
    }

    public function testRefreshTokenIsUuid(): void
    {
        $pair = $this->service->issueTokenPair($this->user);

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $pair->refreshToken,
        );
    }

    public function testVerifyAccessTokenThrowsOnInvalidToken(): void
    {
        $this->expectException(InvalidTokenException::class);
        $this->service->verifyAccessToken('not.a.valid.jwt');
    }

    public function testVerifyAccessTokenThrowsOnExpiredToken(): void
    {
        $expired = new JwtTokenService('test-secret-key-at-least-32-chars!', -1);
        $pair    = $expired->issueTokenPair($this->user);

        $this->expectException(InvalidTokenException::class);
        $expired->verifyAccessToken($pair->accessToken);
    }

    public function testTwoCallsProduceDifferentRefreshTokens(): void
    {
        $pair1 = $this->service->issueTokenPair($this->user);
        $pair2 = $this->service->issueTokenPair($this->user);

        self::assertNotSame($pair1->refreshToken, $pair2->refreshToken);
    }
}
