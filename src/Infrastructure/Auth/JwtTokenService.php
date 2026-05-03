<?php

declare(strict_types=1);

namespace App\Infrastructure\Auth;

use App\Application\Auth\TokenPair;
use App\Application\Auth\TokenServiceInterface;
use App\Domain\Entity\User;
use App\Domain\Exception\InvalidTokenException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtTokenService implements TokenServiceInterface
{
    public function __construct(
        private readonly string $secret,
        private readonly int $accessTtl,
    ) {
    }

    public function issueTokenPair(User $user): TokenPair
    {
        $now = time();
        $jti = $this->generateUuid();

        $payload = [
            'sub'   => $user->getId()->value,
            'email' => $user->getEmail()->value,
            'role'  => $user->getRole()->value,
            'iat'   => $now,
            'exp'   => $now + $this->accessTtl,
            'jti'   => $jti,
        ];

        $accessToken = JWT::encode($payload, $this->secret, 'HS256');

        return new TokenPair($accessToken, $jti, $this->accessTtl);
    }

    /** @return array<string, mixed> */
    public function verifyAccessToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));

            return (array) $decoded;
        } catch (\Throwable $e) {
            throw new InvalidTokenException('Invalid or expired token: ' . $e->getMessage());
        }
    }

    private function generateUuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
