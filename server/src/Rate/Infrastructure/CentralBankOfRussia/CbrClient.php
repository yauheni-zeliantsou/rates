<?php

declare(strict_types=1);

namespace App\Rate\Infrastructure\CentralBankOfRussia;

use App\Rate\Domain\Entity\RateCollection;
use App\Rate\Domain\Interface\RateSourceInterface;
use App\Support\Http\HttpClientInterface;
use DateTimeImmutable;

final readonly class CbrClient implements RateSourceInterface
{
    private const string BASE_URL = 'https://www.cbr.ru/scripts/XML_daily.asp';
    private const string DATE_REQ_FORMAT = 'd/m/Y';

    public function __construct(
        private HttpClientInterface $httpClient,
        private CbrXmlParser $parser,
    ) {
    }

    /**
     * @param DateTimeImmutable[] $dates
     */
    public function getRates(array $dates): RateCollection
    {
        $rates = new RateCollection();

        foreach ($dates as $date) {
            $rates = $rates->merged($this->fetchRatesForDate($date));
        }

        return $rates;
    }

    private function fetchRatesForDate(DateTimeImmutable $date): RateCollection
    {
        $xml = $this->fetchXml($date);

        return $this->parser->parse($xml, $date);
    }

    private function fetchXml(DateTimeImmutable $date): string
    {
        $url = $this->buildRequestUrl($date);

        return $this->httpClient->get($url);
    }

    private function buildRequestUrl(DateTimeImmutable $date): string
    {
        return self::BASE_URL . '?' . http_build_query([
                'date_req' => $date->format(self::DATE_REQ_FORMAT),
            ]);
    }
}
