<?php

declare(strict_types=1);

namespace App\Http;

final readonly class Response
{
    private function __construct(
        private array $data,
        private int $status,
        private array $headers = [],
    ) {
    }

    public static function json(array $data, int $status = 200, array $headers = []): self
    {
        return new self($data, $status, $headers);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function send(): void
    {
        http_response_code($this->status);
        header('Content-Type: application/json');

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        echo json_encode($this->data);
    }
}
