<?php

declare(strict_types=1);

namespace Tests\Auth\Controller;

use App\Auth\Controller\TokenController;
use App\Auth\Interface\TokenRepositoryInterface;
use App\Auth\Interface\UserRepositoryInterface;
use App\Auth\Service\TokenService;
use App\Http\Request;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TokenControllerTest extends TestCase
{
    public function testIssueReturnsAccessTokenOnValidCredentials(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->once())
            ->method('verifyCredentials')
            ->with('user@example.com', 'secret123')
            ->willReturn(42);

        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $tokenRepository->expects($this->once())->method('save');

        $tokenService = new TokenService($tokenRepository, $userRepository, 'web-frontend');
        $controller = new TokenController($tokenService);

        $request = new Request('POST', '/oauth/token', [], [
            'grant_type' => 'password',
            'client_id' => 'web-frontend',
            'username' => 'user@example.com',
            'password' => 'secret123',
        ]);

        $response = $controller->issue($request);

        $this->assertSame(200, $response->status());
        $this->assertSame('Bearer', $response->data()['token_type']);
        $this->assertSame(3600, $response->data()['expires_in']);
        $this->assertNotEmpty($response->data()['access_token']);

        $headers = $response->headers();
        $this->assertSame('no-store', $headers['Cache-Control']);
        $this->assertSame('no-cache', $headers['Pragma']);
        $this->assertStringContainsString(
            'session_token=' . $response->data()['access_token'],
            $headers['Set-Cookie'],
        );
        $this->assertStringContainsString('HttpOnly', $headers['Set-Cookie']);
    }

    public function testIssueLetsGrantValidationErrorsFromTheServicePropagate(): void
    {
        $tokenService = new TokenService(
            $this->createStub(TokenRepositoryInterface::class),
            $this->createStub(UserRepositoryInterface::class),
            'web-frontend',
        );
        $controller = new TokenController($tokenService);

        $request = new Request('POST', '/oauth/token', [], ['grant_type' => 'client_credentials']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unsupported_grant_type');

        $controller->issue($request);
    }
}
