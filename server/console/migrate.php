<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Support\Database\MigrationRunner;
use App\Support\Database\PostgresConnection;

$pdo = (new PostgresConnection())->pdo();
$runner = new MigrationRunner($pdo);

match ($argv[1] ?? null) {
    'migrate' => $runner->migrate($argv[2] ?? null),
    'rollback' => $runner->rollback($argv[2] ?? null),
    default => throw new InvalidArgumentException('Usage: migrate.php migrate [name]|rollback'),
};
