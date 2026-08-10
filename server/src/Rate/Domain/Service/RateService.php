<?php

declare(strict_types=1);

namespace App\Rate\Domain\Service;

use App\Rate\Domain\Entity\RateCollection;
use App\Rate\Domain\Interface\RateRepositoryInterface;
use App\Rate\Domain\Interface\RateSourceInterface;
use DateTimeImmutable;

final readonly class RateService
{
    public function __construct(
        private RateSourceInterface $source,
        private RateRepositoryInterface $repository,
    ) {
    }

    /**
     * @param DateTimeImmutable[] $dates
     * @param string[] $currencyCodes
     */
    public function getRates(array $dates, array $currencyCodes): RateCollection
    {
        $rates = $this->repository->getRates($dates, $currencyCodes);
        $missingDates = $rates->filterDatesWithoutRates($dates);

        if (empty($missingDates)) {
            return $rates;
        }

        $fetchedRates = $this->source->getRates($missingDates);

        $this->repository->save($fetchedRates);

        return $rates->merged($fetchedRates)->filteredByCurrencyCodes($currencyCodes);
    }

    /**
     * @param DateTimeImmutable[] $dates
     */
    public function ingest(array $dates): void
    {
        $fetchedRates = $this->source->getRates($dates);

        $this->repository->save($fetchedRates);
    }
}
