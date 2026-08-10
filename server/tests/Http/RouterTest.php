<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Support\Exception\UpstreamUnavailableException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RouterTest extends TestCase
{
    public function testDispatchCallsMatchingHandler(): void
    {
        $router = new Router();
        $router->get('/api/rates', fn (Request $request): Response => Response::json(['ok' => true]));

        $response = $router->dispatch(new Request('GET', '/api/rates', []));

        $this->assertSame(200, $response->status());
        $this->assertSame(['ok' => true], $response->data());
    }

    public function testDispatchReturnsNotFoundWhenRouteIsNotRegistered(): void
    {
        $router = new Router();

        $response = $router->dispatch(new Request('GET', '/unknown', []));

        $this->assertSame(404, $response->status());
    }

    public function testDispatchReturnsBadRequestOnInvalidArgumentException(): void
    {
        $router = new Router();
        $router->get('/api/rates', function (): never {
            throw new InvalidArgumentException('bad input');
        });

        $response = $router->dispatch(new Request('GET', '/api/rates', []));

        $this->assertSame(400, $response->status());
        $this->assertSame(['error' => 'bad input'], $response->data());
    }

    public function testDispatchReturnsServiceUnavailableOnUpstreamUnavailableException(): void
    {
        $router = new Router();
        $router->get('/api/rates', function (): never {
            throw new UpstreamUnavailableException('cbr.ru is down');
        });

        $response = $router->dispatch(new Request('GET', '/api/rates', []));

        $this->assertSame(503, $response->status());
        $this->assertSame(['error' => 'cbr.ru is down'], $response->data());
    }

    public function testDispatchReturnsInternalServerErrorOnUnexpectedThrowable(): void
    {
        $router = new Router();
        $router->get('/api/rates', function (): never {
            throw new RuntimeException('something broke');
        });

        $response = $router->dispatch(new Request('GET', '/api/rates', []));

        $this->assertSame(500, $response->status());
    }

    public function testDispatchCallsMatchingPostHandler(): void
    {
        $router = new Router();
        $router->post('/oauth/token', fn (Request $request): Response => Response::json(['ok' => true]));

        $response = $router->dispatch(new Request('POST', '/oauth/token', []));

        $this->assertSame(200, $response->status());
        $this->assertSame(['ok' => true], $response->data());
    }

    public function testDispatchRunsMiddlewareBeforeHandler(): void
    {
        $router = new Router();
        $middleware = fn (Request $request, callable $next): Response => $next($request);
        $router->get('/api/rates', fn (Request $request): Response => Response::json(['ok' => true]), [$middleware]);

        $response = $router->dispatch(new Request('GET', '/api/rates', []));

        $this->assertSame(200, $response->status());
        $this->assertSame(['ok' => true], $response->data());
    }

    public function testDispatchShortCircuitsWhenMiddlewareDoesNotCallNext(): void
    {
        $router = new Router();
        $middleware = fn (Request $request, callable $next): Response => Response::json(['error' => 'invalid_token'], 401);
        $router->get(
            '/api/rates',
            function (): never {
                throw new RuntimeException('handler should not run');
            },
            [$middleware],
        );

        $response = $router->dispatch(new Request('GET', '/api/rates', []));

        $this->assertSame(401, $response->status());
        $this->assertSame(['error' => 'invalid_token'], $response->data());
    }
}
