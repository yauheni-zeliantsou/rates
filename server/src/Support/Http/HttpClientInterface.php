<?php

declare(strict_types=1);

namespace App\Support\Http;

interface HttpClientInterface
{
    public function get(string $url): string;
}
