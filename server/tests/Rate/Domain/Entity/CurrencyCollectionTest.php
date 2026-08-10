<?php

declare(strict_types=1);

namespace Tests\Rate\Domain\Entity;

use App\Rate\Domain\Entity\Currency;
use App\Rate\Domain\Entity\CurrencyCollection;
use PHPUnit\Framework\TestCase;

final class CurrencyCollectionTest extends TestCase
{
    public function testCountIsZeroInEmptyCollection(): void
    {
        $currencies = new CurrencyCollection();

        $this->assertCount(0, $currencies);
    }

    public function testCountReturnsNumberOfCurrencies(): void
    {
        $currencies = new CurrencyCollection(
            new Currency(code: 'USD', name: 'Доллар США'),
            new Currency(code: 'EUR', name: 'Евро'),
        );

        $this->assertCount(2, $currencies);
    }

    public function testIterationYieldsAllCurrencies(): void
    {
        $firstCurrency = new Currency(code: 'USD', name: 'Доллар США');
        $secondCurrency = new Currency(code: 'EUR', name: 'Евро');
        $currencies = new CurrencyCollection($firstCurrency, $secondCurrency);

        $this->assertSame([$firstCurrency, $secondCurrency], iterator_to_array($currencies, false));
    }
}
