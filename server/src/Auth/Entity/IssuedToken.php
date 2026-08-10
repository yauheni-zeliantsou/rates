<?php

declare(strict_types=1);

namespace App\Auth\Entity;

final readonly class IssuedToken
{
    public function __construct(
        public string $accessToken,
        public int $expiresIn,
    ) {
    }
}
