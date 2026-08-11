<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Service\TokenService;
use App\Http\Request;
use App\Http\Response;

final readonly class TokenController
{
    public function __construct(private TokenService $tokenService)
    {
    }

    public function issue(Request $request): Response
    {
        $issued = $this->tokenService->issuePasswordGrant(
            $request->body('grant_type'),
            $request->body('client_id'),
            $request->body('username'),
            $request->body('password'),
        );

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
}
