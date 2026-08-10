<?php

declare(strict_types=1);

namespace App\Auth;

use App\Auth\Interface\UserRepositoryInterface;
use App\Auth\Service\TokenService;
use App\Http\Request;
use App\Http\Response;

final readonly class TokenController
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TokenService $tokenService,
        private string $clientId,
    ) {
    }

    public function issue(Request $request): Response
    {
        if ($request->body('grant_type') !== 'password') {
            return $this->error('unsupported_grant_type');
        }

        if ($request->body('client_id') !== $this->clientId) {
            return $this->error('invalid_client');
        }

        $email = $request->body('username');
        $password = $request->body('password');

        $userId = ($email !== null && $password !== null)
            ? $this->userRepository->verifyCredentials($email, $password)
            : null;

        if ($userId === null) {
            return $this->error('invalid_grant');
        }

        $issued = $this->tokenService->issue($userId, $this->clientId);

        return Response::json(
            [
                'access_token' => $issued->accessToken,
                'token_type' => 'Bearer',
                'expires_in' => $issued->expiresIn,
            ],
            200,
            [
                'Cache-Control' => 'no-store',
                'Pragma' => 'no-cache',
                'Set-Cookie' => sprintf(
                    '%s=%s; Path=/; Max-Age=%d; HttpOnly; Secure; SameSite=Lax',
                    TokenService::SESSION_COOKIE_NAME,
                    $issued->accessToken,
                    $issued->expiresIn,
                ),
            ],
        );
    }

    private function error(string $error): Response
    {
        return Response::json(['error' => $error], 400);
    }
}
