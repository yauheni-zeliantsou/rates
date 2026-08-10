<?php

declare(strict_types=1);

namespace App\Rate\Infrastructure\CentralBankOfRussia;

use App\Rate\Domain\Entity\Currency;
use App\Rate\Domain\Entity\Rate;
use App\Rate\Domain\Entity\RateCollection;
use DateTimeImmutable;
use SimpleXMLElement;

final readonly class CbrXmlParser
{
    private const string SOURCE_ENCODING = 'windows-1251';
    private const string TARGET_ENCODING = 'UTF-8';
    private const string EFFECTIVE_DATE_FORMAT = 'd.m.Y';

    public function parse(string $xml, DateTimeImmutable $requestedDate): RateCollection
    {
        $document = new SimpleXMLElement($this->convertToUtf8($xml));
        $effectiveDate = $this->parseEffectiveDate($document);

        $rates = [];

        foreach ($document->Valute as $valute) {
            $rates[] = $this->parseRate($valute, $requestedDate, $effectiveDate);
        }

        return new RateCollection(...$rates);
    }

    private function convertToUtf8(string $xml): string
    {
        $xml = iconv(self::SOURCE_ENCODING, self::TARGET_ENCODING, $xml);

        return preg_replace('/encoding="[^"]+"/', 'encoding="' . self::TARGET_ENCODING . '"', $xml, 1);
    }

    private function parseEffectiveDate(SimpleXMLElement $document): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat(self::EFFECTIVE_DATE_FORMAT, (string) $document['Date']);
    }

    private function parseRate(
        SimpleXMLElement $valute,
        DateTimeImmutable $date,
        DateTimeImmutable $effectiveDate
    ): Rate {
        return new Rate(
            date: $date,
            effectiveDate: $effectiveDate,
            currency: new Currency(
                code: (string) $valute->CharCode,
                name: (string) $valute->Name,
            ),
            nominal: (int) $valute->Nominal,
            value: (float) str_replace(',', '.', (string) $valute->Value),
        );
    }
}
