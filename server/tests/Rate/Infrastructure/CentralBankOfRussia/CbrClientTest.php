<?php

declare(strict_types=1);

namespace Tests\Rate\Infrastructure\CentralBankOfRussia;

use App\Rate\Infrastructure\CentralBankOfRussia\CbrClient;
use App\Rate\Infrastructure\CentralBankOfRussia\CbrXmlParser;
use App\Support\Exception\UpstreamUnavailableException;
use App\Support\Http\HttpClientInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class CbrClientTest extends TestCase
{
    public function testGetRatesRequestsUrlWithDateFormattedAsDayMonthYear(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('get')
            ->with('https://www.cbr.ru/scripts/XML_daily.asp?date_req=05%2F08%2F2026')
            ->willReturn($this->fixtureXml());

        $client = new CbrClient($httpClient, new CbrXmlParser());

        $client->getRates([new DateTimeImmutable('2026-08-05')]);
    }

    public function testGetRatesReturnsRatesParsedFromHttpResponse(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())->method('get')->willReturn($this->fixtureXml());

        $client = new CbrClient($httpClient, new CbrXmlParser());

        $requestedDate = new DateTimeImmutable('2026-08-05');
        $rates = $client->getRates([$requestedDate]);

        $this->assertCount(2, $rates);

        $usdRate = iterator_to_array($rates, false)[0];
        $this->assertSame('USD', $usdRate->currency->code);
        $this->assertSame($requestedDate, $usdRate->date);
        $this->assertSame('2026-08-04', $usdRate->effectiveDate->format('Y-m-d'));
    }

    public function testGetRatesSendsSeparateRequestForEachDateAndMergesResults(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->exactly(2))
            ->method('get')
            ->willReturn($this->fixtureXml());

        $client = new CbrClient($httpClient, new CbrXmlParser());

        $rates = $client->getRates([
            new DateTimeImmutable('2026-08-04'),
            new DateTimeImmutable('2026-08-05'),
        ]);

        $this->assertCount(4, $rates);
    }

    public function testGetRatesPropagatesUpstreamUnavailableException(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())
            ->method('get')
            ->willThrowException(new UpstreamUnavailableException('cbr.ru is down'));

        $client = new CbrClient($httpClient, new CbrXmlParser());

        $this->expectException(UpstreamUnavailableException::class);

        $client->getRates([new DateTimeImmutable('2026-08-05')]);
    }

    private function fixtureXml(): string
    {
        return file_get_contents(__DIR__ . '/Fixtures/cbr-daily.xml');
    }
}
