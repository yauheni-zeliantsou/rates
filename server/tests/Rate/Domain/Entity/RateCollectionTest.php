<?php

declare(strict_types=1);

namespace Tests\Rate\Domain\Entity;

use App\Rate\Domain\Entity\RateCollection;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Rate\Support\RateTestFactoryTrait;

final class RateCollectionTest extends TestCase
{
    use RateTestFactoryTrait;

    public function testCountIsZeroInEmptyCollection(): void
    {
        $rates = new RateCollection();

        $this->assertCount(0, $rates);
    }

    public function testCountReturnsNumberOfRates(): void
    {
        $rates = new RateCollection(
            $this->createRate('2026-08-09'),
            $this->createRate('2026-08-10'),
        );

        $this->assertCount(2, $rates);
    }

    public function testIterationYieldsAllRates(): void
    {
        $firstRate = $this->createRate('2026-08-09');
        $secondRate = $this->createRate('2026-08-10');
        $rates = new RateCollection($firstRate, $secondRate);

        $this->assertSame([$firstRate, $secondRate], iterator_to_array($rates, false));
    }

    public function testMergedCombinesItemsFromBothCollections(): void
    {
        $firstRate = $this->createRate('2026-08-09');
        $secondRate = $this->createRate('2026-08-10');
        $rates = new RateCollection($firstRate);
        $additionalRates = new RateCollection($secondRate);

        $mergedRates = $rates->merged($additionalRates);

        $this->assertSame([$firstRate, $secondRate], iterator_to_array($mergedRates, false));
    }

    public function testMergedWithEmptyCollectionReturnsEquivalentCollection(): void
    {
        $rate = $this->createRate('2026-08-09');
        $rates = new RateCollection($rate);

        $mergedRates = $rates->merged(new RateCollection());

        $this->assertSame([$rate], iterator_to_array($mergedRates, false));
    }

    public function testFilteredByCurrencyCodesKeepsOnlyMatchingRates(): void
    {
        $rates = new RateCollection(
            $this->createRate('2026-08-09', 'USD'),
            $this->createRate('2026-08-09', 'EUR'),
        );

        $filteredRates = $rates->filteredByCurrencyCodes(['USD']);

        $this->assertSame(['USD'], $this->extractCurrencyCodes($filteredRates));
    }

    public function testFilteredByCurrencyCodesWithUnknownCodeReturnsEmpty(): void
    {
        $rates = new RateCollection($this->createRate('2026-08-09', 'USD'));

        $filteredRates = $rates->filteredByCurrencyCodes(['EUR']);

        $this->assertCount(0, $filteredRates);
    }

    public function testFilteredByCurrencyCodesWithEmptyCodesReturnsEmpty(): void
    {
        $rates = new RateCollection($this->createRate('2026-08-09', 'USD'));

        $filteredRates = $rates->filteredByCurrencyCodes([]);

        $this->assertCount(0, $filteredRates);
    }

    public function testFilteredByCurrencyCodesIsCaseSensitive(): void
    {
        $rates = new RateCollection($this->createRate('2026-08-09', 'USD'));

        $filteredRates = $rates->filteredByCurrencyCodes(['usd']);

        $this->assertCount(0, $filteredRates);
    }

    public function testMissingDatesReturnsOnlyDatesNotPresent(): void
    {
        $rates = new RateCollection($this->createRate('2026-08-09'));

        $missingDates = $rates->filterDatesWithoutRates([
            new DateTimeImmutable('2026-08-09'),
            new DateTimeImmutable('2026-08-10'),
        ]);

        $this->assertSame(['2026-08-10'], $this->formatDates($missingDates));
    }

    public function testMissingDatesReturnsEmptyWhenAllDatesPresent(): void
    {
        $rates = new RateCollection($this->createRate('2026-08-09'));

        $missingDates = $rates->filterDatesWithoutRates([new DateTimeImmutable('2026-08-09')]);

        $this->assertSame([], $missingDates);
    }

    public function testMissingDatesReturnsAllDatesWhenCollectionEmpty(): void
    {
        $rates = new RateCollection();

        $missingDates = $rates->filterDatesWithoutRates([new DateTimeImmutable('2026-08-09')]);

        $this->assertSame(['2026-08-09'], $this->formatDates($missingDates));
    }

    public function testMissingDatesReturnsEmptyWhenNoDatesRequested(): void
    {
        $rates = new RateCollection($this->createRate('2026-08-09'));

        $missingDates = $rates->filterDatesWithoutRates([]);

        $this->assertSame([], $missingDates);
    }

    /**
     * @return string[]
     */
    private function extractCurrencyCodes(RateCollection $rates): array
    {
        $codes = [];

        foreach ($rates as $rate) {
            $codes[] = $rate->currency->code;
        }

        return $codes;
    }

    /**
     * @param DateTimeImmutable[] $dates
     * @return string[]
     */
    private function formatDates(array $dates): array
    {
        $formatted = [];

        foreach ($dates as $date) {
            $formatted[] = $date->format('Y-m-d');
        }

        return $formatted;
    }
}
