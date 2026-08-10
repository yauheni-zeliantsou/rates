<?php

declare(strict_types=1);

namespace App\Auth\Interface;

use DateTimeImmutable;

interface TokenRepositoryInterface
{
    public function save(string $tokenHash, int $userId, string $clientId, DateTimeImmutable $expiresAt): void;

    public function findActiveUserIdByTokenHash(string $tokenHash): ?int;
}
