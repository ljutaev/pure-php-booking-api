<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\UserRole;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\UserId;

final class User
{
    private UserRole $role;
    private \DateTimeImmutable $createdAt;

    public function __construct(
        private readonly UserId $id,
        private readonly Email $email,
        private string $passwordHash,
        private readonly string $firstName,
        private readonly string $lastName,
    ) {
        $this->role      = UserRole::Customer;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): UserId
    {
        return $this->id;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public static function reconstitute(
        UserId $id,
        Email $email,
        string $passwordHash,
        string $firstName,
        string $lastName,
        UserRole $role,
        \DateTimeImmutable $createdAt,
    ): self {
        $user               = new self($id, $email, $passwordHash, $firstName, $lastName);
        $user->role         = $role;
        $user->createdAt    = $createdAt;

        return $user;
    }
}
