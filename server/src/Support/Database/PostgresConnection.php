<?php

declare(strict_types=1);

namespace App\Support\Database;

use PDO;

final readonly class PostgresConnection
{
    private const string HOST = 'db';
    private const string PORT = '5432';

    public function pdo(): PDO
    {
        return new PDO(
            $this->buildDsn(),
            getenv('POSTGRES_USER'),
            getenv('POSTGRES_PASSWORD'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
    }

    private function buildDsn(): string
    {
        return 'pgsql:host=' . self::HOST . ';port=' . self::PORT . ';dbname=' . getenv('POSTGRES_DB');
    }
}
