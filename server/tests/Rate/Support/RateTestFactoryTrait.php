<?php

declare(strict_types=1);

namespace Tests\Rate\Support;

use App\Rate\Domain\Entity\Currency;
use App\Rate\Domain\Entity\Rate;
use DateTimeImmutable;

trait RateTestFactoryTrait
{
    private function createRate(string $date, string $currencyCode = 'USD'): Rate
    {
        return new Rate(
            date: new DateTimeImmutable($date),
            effectiveDate: new DateTimeImmutable($date),
            currency: new Currency(code: $currencyCode, name: $currencyCode),
            nominal: 1,
            value: 1.0,
        );
    }
}
