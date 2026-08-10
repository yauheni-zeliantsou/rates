<?php

declare(strict_types=1);

namespace Tests\Rate\Infrastructure\CentralBankOfRussia;

use App\Rate\Domain\Entity\RateCollection;
use App\Rate\Infrastructure\CentralBankOfRussia\CbrXmlParser;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CbrXmlParserTest extends TestCase
{
    public function testParseReturnsRateForEachValute(): void
    {
        $rates = $this->parseFixture();

        $this->assertCount(2, $rates);
    }

    public function testParseDecodesCurrencyCodeAndName(): void
    {
        $rates = $this->parseFixture();
        $usdRate = iterator_to_array($rates, false)[0];

        $this->assertSame('USD', $usdRate->currency->code);
        $this->assertSame('Доллар США', $usdRate->currency->name);
    }

    public function testParseConvertsCommaDecimalValueToFloat(): void
    {
        $rates = $this->parseFixture();
        $usdRate = iterator_to_array($rates, false)[0];

        $this->assertEqualsWithDelta(91.2345, $usdRate->value, 0.00001);
    }

    public function testParseCastsNominalToInt(): void
    {
        $rates = $this->parseFixture();
        $eurRate = iterator_to_array($rates, false)[1];

        $this->assertSame(10, $eurRate->nominal);
    }

    public function testParseSetsRequestedDateOnEachRate(): void
    {
        $requestedDate = new DateTimeImmutable('2026-08-05');
        $rates = $this->parseFixture($requestedDate);

        foreach ($rates as $rate) {
            $this->assertSame($requestedDate, $rate->date);
        }
    }

    public function testParseSetsEffectiveDateFromXmlDateAttribute(): void
    {
        $rates = $this->parseFixture();
        $usdRate = iterator_to_array($rates, false)[0];

        $this->assertSame('2026-08-04', $usdRate->effectiveDate->format('Y-m-d'));
    }

    public function testParseReturnsEmptyCollectionWhenNoValuteElements(): void
    {
        $xml = '<?xml version="1.0" encoding="windows-1251"?>'
            . '<ValCurs Date="04.08.2026" name="Foreign Currency Market"></ValCurs>';

        $rates = (new CbrXmlParser())->parse($xml, new DateTimeImmutable('2026-08-05'));

        $this->assertCount(0, $rates);
    }

    private function parseFixture(?DateTimeImmutable $requestedDate = null): RateCollection
    {
        $xml = file_get_contents(__DIR__ . '/Fixtures/cbr-daily.xml');

        return (new CbrXmlParser())->parse($xml, $requestedDate ?? new DateTimeImmutable('2026-08-05'));
    }
}
