<?php

declare(strict_types=1);

namespace App\Rate\Domain\Interface;

use App\Rate\Domain\Entity\RateCollection;

interface RateRepositoryInterface
{
    public function getRates(array $dates, array $currencyCodes): RateCollection;

    public function save(RateCollection $rates): void;
}
