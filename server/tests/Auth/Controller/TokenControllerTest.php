<?php

declare(strict_types=1);

namespace Tests\Auth;

use App\Auth\Interface\TokenRepositoryInterface;
use App\Auth\Interface\UserRepositoryInterface;
use App\Auth\Service\TokenService;
use App\Auth\TokenController;
use App\Http\Request;
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

        $controller = new TokenController($userRepository, new TokenService($tokenRepository), 'web-frontend');

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

    public function testIssueReturnsUnsupportedGrantTypeError(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->never())->method('verifyCredentials');
        $tokenRepository = $this->createStub(TokenRepositoryInterface::class);

        $controller = new TokenController($userRepository, new TokenService($tokenRepository), 'web-frontend');

        $request = new Request('POST', '/oauth/token', [], ['grant_type' => 'client_credentials']);
        $response = $controller->issue($request);

        $this->assertSame(400, $response->status());
        $this->assertSame(['error' => 'unsupported_grant_type'], $response->data());
    }

    public function testIssueReturnsInvalidClientError(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->never())->method('verifyCredentials');
        $tokenRepository = $this->createStub(TokenRepositoryInterface::class);

        $controller = new TokenController($userRepository, new TokenService($tokenRepository), 'web-frontend');

        $request = new Request('POST', '/oauth/token', [], [
            'grant_type' => 'password',
            'client_id' => 'someone-else',
        ]);
        $response = $controller->issue($request);

        $this->assertSame(400, $response->status());
        $this->assertSame(['error' => 'invalid_client'], $response->data());
    }

    public function testIssueReturnsInvalidGrantErrorOnWrongCredentials(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->once())->method('verifyCredentials')->willReturn(null);
        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $tokenRepository->expects($this->never())->method('save');

        $controller = new TokenController($userRepository, new TokenService($tokenRepository), 'web-frontend');

        $request = new Request('POST', '/oauth/token', [], [
            'grant_type' => 'password',
            'client_id' => 'web-frontend',
            'username' => 'user@example.com',
            'password' => 'wrong-password',
        ]);
        $response = $controller->issue($request);

        $this->assertSame(400, $response->status());
        $this->assertSame(['error' => 'invalid_grant'], $response->data());
    }

    public function testIssueReturnsInvalidGrantErrorWhenCredentialsAreMissing(): void
    {
        $userRepository = $this->createMock(UserRepositoryInterface::class);
        $userRepository->expects($this->never())->method('verifyCredentials');
        $tokenRepository = $this->createStub(TokenRepositoryInterface::class);

        $controller = new TokenController($userRepository, new TokenService($tokenRepository), 'web-frontend');

        $request = new Request('POST', '/oauth/token', [], [
            'grant_type' => 'password',
            'client_id' => 'web-frontend',
        ]);
        $response = $controller->issue($request);

        $this->assertSame(400, $response->status());
        $this->assertSame(['error' => 'invalid_grant'], $response->data());
    }
}
