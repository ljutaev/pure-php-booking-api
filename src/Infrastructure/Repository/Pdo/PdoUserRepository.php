<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\Pdo;

use App\Domain\Entity\User;
use App\Domain\Enum\UserRole;
use App\Domain\Exception\EntityNotFoundException;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\ValueObject\Email;
use App\Domain\ValueObject\UserId;

final class PdoUserRepository implements UserRepositoryInterface
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function save(User $user): void
    {
        $stmt = $this->pdo->prepare('
            INSERT INTO users (id, email, password_hash, first_name, last_name, role, created_at)
            VALUES (:id, :email, :password_hash, :first_name, :last_name, :role, :created_at)
            ON CONFLICT (id) DO UPDATE SET
                email         = EXCLUDED.email,
                password_hash = EXCLUDED.password_hash,
                first_name    = EXCLUDED.first_name,
                last_name     = EXCLUDED.last_name,
                role          = EXCLUDED.role
        ');

        assert($stmt instanceof \PDOStatement);

        $stmt->execute([
            ':id'            => $user->getId()->value,
            ':email'         => $user->getEmail()->value,
            ':password_hash' => $user->getPasswordHash(),
            ':first_name'    => $user->getFirstName(),
            ':last_name'     => $user->getLastName(),
            ':role'          => $user->getRole()->value,
            ':created_at'    => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }

    public function findById(UserId $id): User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        assert($stmt instanceof \PDOStatement);
        $stmt->execute([':id' => $id->value]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            throw new EntityNotFoundException("User with id '{$id->value}' not found");
        }

        return $this->hydrate($row);
    }

    public function findByEmail(Email $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE lower(email) = lower(:email)');
        assert($stmt instanceof \PDOStatement);
        $stmt->execute([':email' => $email->value]);

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrate($row) : null;
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): User
    {
        return User::reconstitute(
            new UserId((string) $row['id']),
            new Email((string) $row['email']),
            (string) $row['password_hash'],
            (string) $row['first_name'],
            (string) $row['last_name'],
            UserRole::from((string) $row['role']),
            new \DateTimeImmutable((string) $row['created_at']),
        );
    }
}
