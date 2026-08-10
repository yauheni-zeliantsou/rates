<?php

declare(strict_types=1);

namespace Tests\Auth\Controller;

use App\Auth\Controller\SessionController;
use App\Auth\Interface\TokenRepositoryInterface;
use App\Auth\Service\TokenService;
use App\Http\Request;
use PHPUnit\Framework\TestCase;

final class SessionControllerTest extends TestCase
{
    public function testVerifyReturnsOkWhenCookieIsValid(): void
    {
        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $tokenRepository->expects($this->once())
            ->method('findActiveUserIdByTokenHash')
            ->with(hash('sha256', 'valid-token'))
            ->willReturn(42);

        $controller = new SessionController(new TokenService($tokenRepository));
        $request = new Request('GET', '/auth/verify', [], [], [], [TokenService::SESSION_COOKIE_NAME => 'valid-token']);

        $response = $controller->verify($request);

        $this->assertSame(200, $response->status());
    }

    public function testVerifyReturnsUnauthorizedWhenCookieIsMissing(): void
    {
        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $tokenRepository->expects($this->never())->method('findActiveUserIdByTokenHash');

        $controller = new SessionController(new TokenService($tokenRepository));
        $request = new Request('GET', '/auth/verify', []);

        $response = $controller->verify($request);

        $this->assertSame(401, $response->status());
        $this->assertSame(['error' => 'invalid_token'], $response->data());
    }

    public function testVerifyReturnsUnauthorizedWhenCookieIsInvalid(): void
    {
        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $tokenRepository->expects($this->once())->method('findActiveUserIdByTokenHash')->willReturn(null);

        $controller = new SessionController(new TokenService($tokenRepository));
        $request = new Request('GET', '/auth/verify', [], [], [], [TokenService::SESSION_COOKIE_NAME => 'garbage']);

        $response = $controller->verify($request);

        $this->assertSame(401, $response->status());
    }
}
