<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Auth\Entity\IssuedToken;
use App\Auth\Interface\TokenRepositoryInterface;
use DateTimeImmutable;

final readonly class TokenService
{
    public const string SESSION_COOKIE_NAME = 'session_token';

    private const int TTL_SECONDS = 3600;

    public function __construct(private TokenRepositoryInterface $tokenRepository)
    {
    }

    public function issue(int $userId, string $clientId): IssuedToken
    {
        $token = bin2hex(random_bytes(40));
        $expiresAt = new DateTimeImmutable('+' . self::TTL_SECONDS . ' seconds');

        $this->tokenRepository->save($this->hash($token), $userId, $clientId, $expiresAt);

        return new IssuedToken($token, self::TTL_SECONDS);
    }

    public function resolveUserId(string $token): ?int
    {
        return $this->tokenRepository->findActiveUserIdByTokenHash($this->hash($token));
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
