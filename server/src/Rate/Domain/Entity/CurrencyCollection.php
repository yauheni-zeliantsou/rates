<?php

declare(strict_types=1);

namespace App\Rate\Domain\Entity;

use ArrayIterator;
use Countable;
use Iterator;
use IteratorAggregate;

final readonly class CurrencyCollection implements IteratorAggregate, Countable
{
    private array $items;

    public function __construct(Currency ...$currencies)
    {
        $this->items = $currencies;
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
