<?php

declare(strict_types=1);

namespace Tests\Auth;

use App\Auth\Interface\TokenRepositoryInterface;
use App\Auth\Service\TokenService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class TokenServiceTest extends TestCase
{
    public function testIssueGeneratesTokenHashesItAndPersistsWithTtl(): void
    {
        $capturedHash = null;

        $repository = $this->createMock(TokenRepositoryInterface::class);
        $repository->expects($this->once())
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

        $service = new TokenService($repository);
        $result = $service->issue(42, 'web-frontend');

        $this->assertSame(80, strlen($result->accessToken));
        $this->assertSame(3600, $result->expiresIn);
        $this->assertSame(hash('sha256', $result->accessToken), $capturedHash);
    }

    public function testResolveUserIdHashesTokenAndDelegatesToRepository(): void
    {
        $repository = $this->createMock(TokenRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('findActiveUserIdByTokenHash')
            ->with(hash('sha256', 'raw-token'))
            ->willReturn(7);

        $service = new TokenService($repository);

        $this->assertSame(7, $service->resolveUserId('raw-token'));
    }

    public function testResolveUserIdReturnsNullWhenTokenNotFound(): void
    {
        $repository = $this->createMock(TokenRepositoryInterface::class);
        $repository->expects($this->once())->method('findActiveUserIdByTokenHash')->willReturn(null);

        $service = new TokenService($repository);

        $this->assertNull($service->resolveUserId('unknown-token'));
    }
}
