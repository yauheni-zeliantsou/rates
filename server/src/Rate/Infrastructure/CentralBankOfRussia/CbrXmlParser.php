<?php

declare(strict_types=1);

namespace App\Rate\Infrastructure\CentralBankOfRussia;

use App\Rate\Domain\Entity\Currency;
use App\Rate\Domain\Entity\Rate;
use App\Rate\Domain\Entity\RateCollection;
use App\Support\Exception\UpstreamUnavailableException;
use DateTimeImmutable;
use Exception;
use SimpleXMLElement;

final readonly class CbrXmlParser
{
    private const string SOURCE_ENCODING = 'windows-1251';
    private const string TARGET_ENCODING = 'UTF-8';
    private const string EFFECTIVE_DATE_FORMAT = 'd.m.Y';

    public function parse(string $xml, DateTimeImmutable $requestedDate): RateCollection
    {
        $document = $this->parseXml($xml);
        $effectiveDate = $this->parseEffectiveDate($document);

        $rates = [];

        foreach ($document->Valute as $valute) {
            $rates[] = $this->parseRate($valute, $requestedDate, $effectiveDate);
        }

        return new RateCollection(...$rates);
    }

    private function parseXml(string $xml): SimpleXMLElement
    {
        try {
            return new SimpleXMLElement($this->convertToUtf8($xml));
        } catch (Exception $exception) {
            throw new UpstreamUnavailableException(
                'Failed to parse CBR response: ' . $exception->getMessage(),
                previous: $exception,
            );
        }
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
