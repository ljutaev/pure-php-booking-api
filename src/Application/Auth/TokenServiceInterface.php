<?php

declare(strict_types=1);

namespace App\Application\Auth;

use App\Domain\Entity\User;

interface TokenServiceInterface
{
    public function issueTokenPair(User $user): TokenPair;

    /** @return array<string, mixed> */
    public function verifyAccessToken(string $token): array;
}
