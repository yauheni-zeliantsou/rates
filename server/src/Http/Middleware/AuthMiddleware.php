<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Service\TokenService;
use App\Http\Request;
use App\Http\Response;

final readonly class AuthMiddleware
{
    public function __construct(private TokenService $tokenService)
    {
    }

    public function __invoke(Request $request, callable $next): Response
    {
        $header = $request->header('Authorization');

        if ($header === null || !str_starts_with($header, 'Bearer ')) {
            return $this->unauthorized();
        }

        $userId = $this->tokenService->resolveUserId(substr($header, strlen('Bearer ')));

        if ($userId === null) {
            return $this->unauthorized();
        }

        return $next($request);
    }

    private function unauthorized(): Response
    {
        return Response::json(
            ['error' => 'invalid_token'],
            401,
            ['WWW-Authenticate' => 'Bearer error="invalid_token"'],
        );
    }
}
