<?php

declare(strict_types=1);

namespace App\Support\Logging;

interface LoggerInterface
{
    public function warning(string $message, array $context = []): void;

    public function error(string $message, array $context = []): void;
}
