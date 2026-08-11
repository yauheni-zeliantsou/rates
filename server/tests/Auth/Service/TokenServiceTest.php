<?php

declare(strict_types=1);

namespace Tests\Auth\Service;

use App\Auth\Interface\TokenRepositoryInterface;
use App\Auth\Interface\UserRepositoryInterface;
use App\Auth\Service\TokenService;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TokenServiceTest extends TestCase
{
    public function testIssueGeneratesTokenHashesItAndPersistsWithTtl(): void
    {
        $capturedHash = null;

        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $tokenRepository->expects($this->once())
            ->method('save')
            ->with(
                $this->callback(function (string $hash) use (&$capturedHash): bool {
                    $capturedHash = $hash;

                    return true;
                }),
                42,
                'web-frontend',
                $this->isInstanceOf(DateTimeImmutable::class),
            );

        $service = $this->createTokenService($tokenRepository);
        $result = $service->issue(42, 'web-frontend');

        $this->assertSame(80, strlen($result->accessToken));
        $this->assertSame(3600, $result->expiresIn);
        $this->assertSame(hash('sha256', $result->accessToken), $capturedHash);
    }

    public function testResolveUserIdHashesTokenAndDelegatesToRepository(): void
    {
        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $tokenRepository->expects($this->once())
            ->method('findActiveUserIdByTokenHash')
            ->with(hash('sha256', 'raw-token'))
            ->willReturn(7);

        $service = $this->createTokenService($tokenRepository);

        $this->assertSame(7, $service->resolveUserId('raw-token'));
    }

    public function testResolveUserIdReturnsNullWhenTokenNotFound(): void
    {
        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $tokenRepository->expects($this->once())->method('findActiveUserIdByTokenHash')->willReturn(null);

        $service = $this->createTokenService($tokenRepository);

        $this->assertNull($service->resolveUserId('unknown-token'));
    }

    public function testIssuePasswordGrantReturnsTokenOnValidCredentials(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->once())
            ->method('verifyCredentials')
            ->with('user@example.com', 'secret123')
            ->willReturn(42);

        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $tokenRepository->expects($this->once())->method('save');

        $service = $this->createTokenService($tokenRepository, $userRepository);
        $result = $service->issuePasswordGrant('password', 'web-frontend', 'user@example.com', 'secret123');

        $this->assertSame(3600, $result->expiresIn);
        $this->assertNotEmpty($result->accessToken);
    }

    public function testIssuePasswordGrantThrowsOnUnsupportedGrantType(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->never())->method('verifyCredentials');

        $service = $this->createTokenService($this->createStub(TokenRepositoryInterface::class), $userRepository);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unsupported_grant_type');

        $service->issuePasswordGrant('client_credentials', 'web-frontend', null, null);
    }

    public function testIssuePasswordGrantThrowsOnInvalidClient(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->never())->method('verifyCredentials');

        $service = $this->createTokenService($this->createStub(TokenRepositoryInterface::class), $userRepository);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid_client');

        $service->issuePasswordGrant('password', 'someone-else', null, null);
    }

    public function testIssuePasswordGrantThrowsOnWrongCredentials(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->once())->method('verifyCredentials')->willReturn(null);

        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $tokenRepository->expects($this->never())->method('save');

        $service = $this->createTokenService($tokenRepository, $userRepository);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid_grant');

        $service->issuePasswordGrant('password', 'web-frontend', 'user@example.com', 'wrong-password');
    }

    public function testIssuePasswordGrantThrowsWhenCredentialsAreMissing(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->never())->method('verifyCredentials');

        $service = $this->createTokenService($this->createStub(TokenRepositoryInterface::class), $userRepository);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid_grant');

        $service->issuePasswordGrant('password', 'web-frontend', null, null);
    }

    private function createTokenService(
        TokenRepositoryInterface $tokenRepository,
        ?UserRepositoryInterface $userRepository = null,
    ): TokenService {
        return new TokenService(
            $tokenRepository,
            $userRepository ?? $this->createStub(UserRepositoryInterface::class),
            'web-frontend',
        );
    }
}
