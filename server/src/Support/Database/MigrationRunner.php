<?php

declare(strict_types=1);

namespace App\Support\Database;

use PDO;

final readonly class MigrationRunner
{
    private const string MIGRATIONS_PATH = __DIR__ . '/migrations';
    private const string REGISTRY_TABLE = 'schema_migrations';

    public function __construct(private PDO $pdo)
    {
    }

    public function migrate(?string $name = null): void
    {
        $this->ensureRegistryExists();

        $names = $name ? [$name] : $this->getPendingMigrationNames();

        foreach ($names as $migrationName) {
            $this->applyMigration($migrationName);
        }
    }

    public function rollback(?string $name = null): void
    {
        $name ??= $this->lastAppliedMigrationName();

        if ($name) {
            $this->revertMigration($name);
        }
    }

    private function ensureRegistryExists(): void
    {
        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS ' . self::REGISTRY_TABLE . ' (
                version VARCHAR(255) PRIMARY KEY,
                applied_at TIMESTAMP NOT NULL DEFAULT NOW()
            )'
        );
    }

    /**
     * @return string[]
     */
    private function getPendingMigrationNames(): array
    {
        $pending = [];
        $applied = $this->getAppliedMigrationNames();
        $upSqlFiles = glob(self::MIGRATIONS_PATH . '/*.up.sql');

        foreach ($upSqlFiles as $file) {
            $name = basename($file, '.up.sql');

            if (!in_array($name, $applied, true)) {
                $pending[] = $name;
            }
        }

        sort($pending);

        return $pending;
    }

    /**
     * @return string[]
     */
    private function getAppliedMigrationNames(): array
    {
        $statement = $this->pdo->query('SELECT version FROM ' . self::REGISTRY_TABLE);

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    private function applyMigration(string $name): void
    {
        $sql = file_get_contents($this->buildMigrationFilePath($name, 'up'));

        $this->pdo->beginTransaction();

        $this->pdo->exec($sql);

        $statement = $this->pdo->prepare('INSERT INTO ' . self::REGISTRY_TABLE . ' (version) VALUES (?)');
        $statement->execute([$name]);

        $this->pdo->commit();
    }

    private function buildMigrationFilePath(string $name, string $suffix): string
    {
        return self::MIGRATIONS_PATH . '/' . $name . '.' . $suffix . '.sql';
    }

    private function lastAppliedMigrationName(): ?string
    {
        $statement = $this->pdo->query(
            'SELECT version FROM ' . self::REGISTRY_TABLE . ' ORDER BY applied_at DESC LIMIT 1'
        );
        $name = $statement->fetchColumn();

        return $name !== false ? $name : null;
    }

    private function revertMigration(string $name): void
    {
        $sql = file_get_contents($this->buildMigrationFilePath($name, 'down'));

        $this->pdo->beginTransaction();

        $this->pdo->exec($sql);

        $statement = $this->pdo->prepare('DELETE FROM ' . self::REGISTRY_TABLE . ' WHERE version = ?');
        $statement->execute([$name]);

        $this->pdo->commit();
    }
}
