<?php

declare(strict_types=1);

namespace App\Auth\Service;

use App\Auth\Entity\IssuedToken;
use App\Auth\Interface\TokenRepositoryInterface;
use App\Auth\Interface\UserRepositoryInterface;
use DateMalformedStringException;
use DateTimeImmutable;
use InvalidArgumentException;
use Random\RandomException;

final readonly class TokenService
{
    public const string SESSION_COOKIE_NAME = 'session_token';

    private const int TTL_SECONDS = 3600;

    public function __construct(
        private TokenRepositoryInterface $tokenRepository,
        private UserRepositoryInterface $userRepository,
        private string $clientId,
    ) {
    }

    public function issuePasswordGrant(
        ?string $grantType,
        ?string $clientId,
        ?string $email,
        ?string $password,
    ): IssuedToken {
        if ($grantType !== 'password') {
            throw new InvalidArgumentException('unsupported_grant_type');
        }

        if ($clientId !== $this->clientId) {
            throw new InvalidArgumentException('invalid_client');
        }

        $userId = ($email !== null && $password !== null)
            ? $this->userRepository->verifyCredentials($email, $password)
            : null;

        if ($userId === null) {
            throw new InvalidArgumentException('invalid_grant');
        }

        return $this->issue($userId, $this->clientId);
    }

    /**
     * @throws DateMalformedStringException
     * @throws RandomException
     */
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
