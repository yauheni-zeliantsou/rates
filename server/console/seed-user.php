<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Support\Database\PostgresConnection;

$email = $argv[1] ?? null;
$password = $argv[2] ?? null;

if ($email === null || $password === null) {
    fwrite(STDERR, 'Usage: seed-user.php <email> <password>' . PHP_EOL);
    exit(1);
}

$pdo = (new PostgresConnection())->pdo();

$statement = $pdo->prepare('INSERT INTO users (email, password_hash) VALUES (?, ?)');
$statement->execute([$email, password_hash($password, PASSWORD_BCRYPT)]);

fwrite(STDOUT, "Created user {$email}" . PHP_EOL);
