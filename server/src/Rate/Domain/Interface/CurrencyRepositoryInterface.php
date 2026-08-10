<?php

declare(strict_types=1);

namespace App\Rate\Domain\Interface;

use App\Rate\Domain\Entity\CurrencyCollection;

interface CurrencyRepositoryInterface
{
    public function getAll(): CurrencyCollection;
}
