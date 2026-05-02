<?php

declare(strict_types=1);

$dsn = sprintf(
    'pgsql:host=%s;port=%s;dbname=%s',
    getenv('DB_HOST') ?: 'localhost',
    getenv('DB_PORT') ?: '5432',
    getenv('DB_NAME') ?: 'hotel_booking',
);
$user = (string) (getenv('DB_USER') ?: 'hotel_user');
$pass = (string) (getenv('DB_PASSWORD') ?: 'secret');

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (\PDOException $e) {
    fwrite(STDERR, 'Connection failed: ' . $e->getMessage() . "\n");
    exit(1);
}

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        version    VARCHAR(255) PRIMARY KEY,
        applied_at TIMESTAMPTZ  NOT NULL DEFAULT now()
    )'
);

$applied = $pdo->query('SELECT version FROM schema_migrations ORDER BY version')
    ->fetchAll(PDO::FETCH_COLUMN);
$applied = array_flip($applied);

$migrationDir = __DIR__ . '/../migrations';
$files = glob($migrationDir . '/*.sql');

if ($files === false || $files === []) {
    echo "No migration files found in {$migrationDir}.\n";
    exit(0);
}

sort($files);

foreach ($files as $file) {
    $version = basename($file);

    if (isset($applied[$version])) {
        echo "  [skip] {$version}\n";

        continue;
    }

    $sql = file_get_contents($file);

    if ($sql === false) {
        fwrite(STDERR, "Cannot read {$file}\n");
        exit(1);
    }

    $pdo->exec($sql);

    $stmt = $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)');
    $stmt->execute([$version]);

    echo "  [done] {$version}\n";
}

echo "Migrations complete.\n";