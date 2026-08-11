<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Support\Database\PostgresConnection;
use App\Support\Logging\ErrorLogger;

$email = $argv[1] ?? null;
$password = $argv[2] ?? null;

if ($email === null || $password === null) {
    fwrite(STDERR, 'Usage: seed-user.php <email> <password>' . PHP_EOL);
    exit(1);
}

$logger = new ErrorLogger();

try {
    $pdo = (new PostgresConnection())->pdo();

    $statement = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
    $statement->execute([$email, password_hash($password, PASSWORD_BCRYPT)]);
} catch (Throwable $exception) {
    $logger->error($exception->getMessage(), [
        'exception' => $exception::class,
        'file' => $exception->getFile() . ':' . $exception->getLine(),
    ]);
    fwrite(STDERR, 'Failed to create user: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, "Created user {$email}" . PHP_EOL);
