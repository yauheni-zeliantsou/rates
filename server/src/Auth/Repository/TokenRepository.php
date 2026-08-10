<?php

declare(strict_types=1);

namespace App\Auth\Repository;

use App\Auth\Interface\TokenRepositoryInterface;
use DateTimeImmutable;
use PDO;

final readonly class TokenRepository implements TokenRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function save(string $tokenHash, int $userId, string $clientId, DateTimeImmutable $expiresAt): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO oauth_tokens (token_hash, user_id, client_id, expires_at) VALUES (?, ?, ?, ?)'
        );
        $statement->execute([$tokenHash, $userId, $clientId, $expiresAt->format('Y-m-d H:i:s')]);
    }

    public function findActiveUserIdByTokenHash(string $tokenHash): ?int
    {
        $statement = $this->pdo->prepare(
            'SELECT user_id FROM oauth_tokens WHERE token_hash = ? AND revoked = FALSE AND expires_at > NOW()'
        );
        $statement->execute([$tokenHash]);
        $userId = $statement->fetchColumn();

        return $userId !== false ? (int) $userId : null;
    }
}
