<?php

declare(strict_types=1);

namespace App\Http;

use App\Support\Exception\UpstreamUnavailableException;
use InvalidArgumentException;
use Throwable;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler, array $middlewares = []): void
    {
        $this->routes['GET'][$path] = ['handler' => $handler, 'middlewares' => $middlewares];
    }

    public function post(string $path, callable $handler, array $middlewares = []): void
    {
        $this->routes['POST'][$path] = ['handler' => $handler, 'middlewares' => $middlewares];
    }

    public function dispatch(Request $request): Response
    {
        $route = $this->routes[$request->method()][$request->path()] ?? null;

        if ($route === null) {
            return Response::json(['error' => 'Not Found'], 404);
        }

        try {
            return $this->runPipeline($route['handler'], $route['middlewares'], $request);
        } catch (InvalidArgumentException $exception) {
            return Response::json(['error' => $exception->getMessage()], 400);
        } catch (UpstreamUnavailableException $exception) {
            return Response::json(['error' => $exception->getMessage()], 503);
        } catch (Throwable) {
            return Response::json(['error' => 'Internal Server Error'], 500);
        }
    }

    private function runPipeline(callable $handler, array $middlewares, Request $request): Response
    {
        $next = $handler;

        foreach (array_reverse($middlewares) as $middleware) {
            $next = fn (Request $request): Response => $middleware($request, $next);
        }

        return $next($request);
    }
}
