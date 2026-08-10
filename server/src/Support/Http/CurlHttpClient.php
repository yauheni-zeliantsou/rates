<?php

declare(strict_types=1);

namespace App\Support\Http;

use App\Support\Exception\UpstreamUnavailableException;
use CurlHandle;

final readonly class CurlHttpClient implements HttpClientInterface
{
    private const int TIMEOUT_SECONDS = 5;

    public function get(string $url): string
    {
        $handle = $this->openHandle($url);
        $response = $this->execute($handle);

        $this->closeHandle($handle);

        return $response;
    }

    private function openHandle(string $url): CurlHandle
    {
        $handle = curl_init($url);

        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_TIMEOUT, self::TIMEOUT_SECONDS);

        return $handle;
    }

    private function execute(CurlHandle $handle): string
    {
        $response = curl_exec($handle);

        if ($response === false) {
            throw new UpstreamUnavailableException('cURL request failed: ' . curl_error($handle));
        }

        return $response;
    }

    private function closeHandle(CurlHandle $handle): void
    {
        curl_close($handle);
    }
}
