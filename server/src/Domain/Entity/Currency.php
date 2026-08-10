<?php

declare(strict_types=1);

namespace App\Rate\Domain\Entity;

final readonly class Currency
{
    public function __construct(
        public readonly string $code,
        public readonly string $name,
    ) {
    }
}
