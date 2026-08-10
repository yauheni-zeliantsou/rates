<?php

declare(strict_types=1);

namespace App\Rate\Domain\Interface;

use App\Rate\Domain\Entity\RateCollection;

interface RateSourceInterface
{
    public function getRates(array $dates): RateCollection;
}
