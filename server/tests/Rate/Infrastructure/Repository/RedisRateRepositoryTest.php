<?php

declare(strict_types=1);

namespace Tests\Rate\Infrastructure\Repository;

use App\Rate\Domain\Entity\RateCollection;
use App\Rate\Infrastructure\Repository\RedisRateRepository;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Redis;
use Tests\Rate\Support\RateTestFactoryTrait;

final class RedisRateRepositoryTest extends TestCase
{
    use RateTestFactoryTrait;

    public function testGetRatesReturnsRatesForKeysFoundInCache(): void
    {
        $dates = [new DateTimeImmutable('2026-08-09')];
        $currencyCodes = ['USD'];

        $redis = $this->createMock(Redis::class);
        $redis->expects($this->once())
            ->method('mget')
            ->with(['rate:2026-08-09:USD'])
            ->willReturn([$this->encodeRate('2026-08-09', 'USD')]);

        $repository = new RedisRateRepository($redis);
        $rates = $repository->getRates($dates, $currencyCodes);

        $this->assertCount(1, $rates);

        $rate = iterator_to_array($rates, false)[0];
        $this->assertSame('USD', $rate->currency->code);
        $this->assertSame('2026-08-09', $rate->date->format('Y-m-d'));
    }

    public function testGetRatesSkipsKeysMissingFromCache(): void
    {
        $dates = [new DateTimeImmutable('2026-08-09'), new DateTimeImmutable('2026-08-10')];
        $currencyCodes = ['USD'];

        $redis = $this->createMock(Redis::class);
        $redis->expects($this->once())
            ->method('mget')
            ->with(['rate:2026-08-09:USD', 'rate:2026-08-10:USD'])
            ->willReturn([$this->encodeRate('2026-08-09', 'USD'), false]);

        $repository = new RedisRateRepository($redis);
        $rates = $repository->getRates($dates, $currencyCodes);

        $this->assertCount(1, $rates);
    }

    public function testGetRatesBuildsKeyForEachDateAndCurrencyCombination(): void
    {
        $dates = [new DateTimeImmutable('2026-08-09'), new DateTimeImmutable('2026-08-10')];
        $currencyCodes = ['USD', 'EUR'];

        $redis = $this->createMock(Redis::class);
        $redis->expects($this->once())
            ->method('mget')
            ->with([
                'rate:2026-08-09:USD',
                'rate:2026-08-09:EUR',
                'rate:2026-08-10:USD',
                'rate:2026-08-10:EUR',
            ])
            ->willReturn([false, false, false, false]);

        $repository = new RedisRateRepository($redis);
        $rates = $repository->getRates($dates, $currencyCodes);

        $this->assertCount(0, $rates);
    }

    public function testSaveWritesRatesKeyedByDateAndCurrencyCode(): void
    {
        $rate = $this->createRate('2026-08-09', 'USD');
        $rates = new RateCollection($rate);

        $redis = $this->createMock(Redis::class);
        $redis->expects($this->once())
            ->method('mset')
            ->with(['rate:2026-08-09:USD' => $this->encodeRate('2026-08-09', 'USD')]);

        $repository = new RedisRateRepository($redis);
        $repository->save($rates);
    }

    private function encodeRate(string $date, string $currencyCode): string
    {
        return json_encode([
            'date' => $date,
            'effective_date' => $date,
            'currency_code' => $currencyCode,
            'currency_name' => $currencyCode,
            'nominal' => 1,
            'value' => 1.0,
        ]);
    }
}
