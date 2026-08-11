<?php

declare(strict_types=1);

namespace Tests\Http\Middleware;

use App\Auth\Interface\TokenRepositoryInterface;
use App\Auth\Interface\UserRepositoryInterface;
use App\Auth\Service\TokenService;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Request;
use App\Http\Response;
use PHPUnit\Framework\TestCase;

final class AuthMiddlewareTest extends TestCase
{
    public function testInvokeCallsNextWhenTokenIsValid(): void
    {
        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $tokenRepository->expects($this->once())
            ->method('findActiveUserIdByTokenHash')
            ->with(hash('sha256', 'valid-token'))
            ->willReturn(42);

        $middleware = new AuthMiddleware($this->createTokenService($tokenRepository));
        $request = new Request('GET', '/api/rates', [], [], ['Authorization' => 'Bearer valid-token']);

        $response = $middleware($request, fn (Request $request): Response => Response::json(['ok' => true]));

        $this->assertSame(200, $response->status());
        $this->assertSame(['ok' => true], $response->data());
    }

    public function testInvokeReturnsUnauthorizedWhenHeaderIsMissing(): void
    {
        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $tokenRepository->expects($this->never())->method('findActiveUserIdByTokenHash');

        $middleware = new AuthMiddleware($this->createTokenService($tokenRepository));
        $request = new Request('GET', '/api/rates', []);

        $response = $middleware($request, function (): never {
            throw new \RuntimeException('next should not be called');
        });

        $this->assertSame(401, $response->status());
        $this->assertSame(['error' => 'invalid_token'], $response->data());
        $this->assertSame(['WWW-Authenticate' => 'Bearer error="invalid_token"'], $response->headers());
    }

    public function testInvokeReturnsUnauthorizedWhenTokenIsInvalid(): void
    {
        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $tokenRepository->expects($this->once())->method('findActiveUserIdByTokenHash')->willReturn(null);

        $middleware = new AuthMiddleware($this->createTokenService($tokenRepository));
        $request = new Request('GET', '/api/rates', [], [], ['Authorization' => 'Bearer garbage']);

        $response = $middleware($request, function (): never {
            throw new \RuntimeException('next should not be called');
        });

        $this->assertSame(401, $response->status());
    }

    private function createTokenService(TokenRepositoryInterface $tokenRepository): TokenService
    {
        return new TokenService($tokenRepository, $this->createStub(UserRepositoryInterface::class), 'web-frontend');
    }
}
