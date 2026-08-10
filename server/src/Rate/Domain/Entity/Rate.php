<?php

declare(strict_types=1);

namespace App\Rate\Domain\Entity;

use DateTimeImmutable;

final readonly class Rate
{
    public function __construct(
        public readonly DateTimeImmutable $date,
        public readonly DateTimeImmutable $effectiveDate,
        public readonly Currency $currency,
        public readonly int $nominal,
        public readonly float $value,
    ) {
    }
}
