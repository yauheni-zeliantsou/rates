<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Service\TokenService;
use App\Http\Request;
use App\Http\Response;

final readonly class SessionController
{
    public function __construct(private TokenService $tokenService)
    {
    }

    public function verify(Request $request): Response
    {
        $token = $request->cookie(TokenService::SESSION_COOKIE_NAME);
        $userId = $token !== null ? $this->tokenService->resolveUserId($token) : null;

        if ($userId === null) {
            return Response::json(['error' => 'invalid_token'], 401);
        }

        return Response::json([], 200);
    }
}
