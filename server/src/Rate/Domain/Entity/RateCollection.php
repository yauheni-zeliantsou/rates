<?php

declare(strict_types=1);

namespace App\Rate\Domain\Entity;

use ArrayIterator;
use Countable;
use DateTimeImmutable;
use Iterator;
use IteratorAggregate;

final readonly class RateCollection implements IteratorAggregate, Countable
{
    private const string DATE_FORMAT = 'Y-m-d';

    private array $items;

    public function __construct(Rate ...$rates)
    {
        $this->items = $rates;
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function merged(self $additional): self
    {
        return new self(...$this->items, ...$additional->items);
    }

    /**
     * @param string[] $currencyCodes
     */
    public function filteredByCurrencyCodes(array $currencyCodes): self
    {
        $filteredArray = array_filter(
            $this->items,
            static fn (Rate $rate): bool => in_array($rate->currency->code, $currencyCodes)
        );

        return new self(...$filteredArray);
    }

    /**
     * @param DateTimeImmutable[] $dates
     * @return DateTimeImmutable[]
     */
    public function filterDatesWithoutRates(array $dates): array
    {
        $existingDates = [];
        $missingDates = [];

        foreach ($this->items as $rate) {
            $existingDates[$rate->date->format(self::DATE_FORMAT)] = true;
        }

        foreach ($dates as $date) {
            if (!isset($existingDates[$date->format(self::DATE_FORMAT)])) {
                $missingDates[] = $date;
            }
        }

        return $missingDates;
    }
}
