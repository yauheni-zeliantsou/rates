<?php

declare(strict_types=1);

namespace App\Http;

final readonly class Request
{
    public function __construct(
        private string $method,
        private string $path,
        private array $query,
        private array $body = [],
        private array $headers = [],
        private array $cookies = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        return new self(
            method: $_SERVER['REQUEST_METHOD'],
            path: parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            query: $_GET,
            body: $_POST,
            headers: getallheaders(),
            cookies: $_COOKIE,
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key): ?string
    {
        return $this->query[$key] ?? null;
    }

    public function body(string $key): ?string
    {
        return $this->body[$key] ?? null;
    }

    public function cookie(string $name): ?string
    {
        return $this->cookies[$name] ?? null;
    }

    public function header(string $name): ?string
    {
        foreach ($this->headers as $headerName => $value) {
            if (strcasecmp($headerName, $name) === 0) {
                return $value;
            }
        }

        return null;
    }
}
