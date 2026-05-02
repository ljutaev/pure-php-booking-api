<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    protected \PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $host = (string) (getenv('DB_HOST') ?: 'postgres');
        $port = (string) (getenv('DB_PORT') ?: '5432');
        $name = (string) (getenv('DB_NAME') ?: 'hotel_booking');
        $user = (string) (getenv('DB_USER') ?: 'hotel_user');
        $pass = (string) (getenv('DB_PASSWORD') ?: 'secret');

        $this->pdo = new \PDO(
            "pgsql:host={$host};port={$port};dbname={$name}",
            $user,
            $pass,
            [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ],
        );

        $this->runMigrations();
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }

        parent::tearDown();
    }

    private function runMigrations(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS schema_migrations (
                version    VARCHAR(255) PRIMARY KEY,
                applied_at TIMESTAMPTZ  NOT NULL DEFAULT now()
            )'
        );

        $stmt = $this->pdo->query('SELECT version FROM schema_migrations');
        assert($stmt instanceof \PDOStatement);
        $applied = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $applied = array_flip($applied);

        $files = glob(__DIR__ . '/../../migrations/*.sql') ?: [];
        sort($files);

        foreach ($files as $file) {
            $version = basename($file);

            if (!isset($applied[$version])) {
                $this->pdo->exec((string) file_get_contents($file));
                $this->pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)')
                    ->execute([$version]);
            }
        }
    }
}
