<?php

declare(strict_types=1);

namespace App\Rate\Infrastructure\Repository;

use App\Rate\Domain\Entity\Currency;
use App\Rate\Domain\Entity\Rate;
use App\Rate\Domain\Entity\RateCollection;
use App\Rate\Domain\Interface\RateRepositoryInterface;
use DateTimeImmutable;
use Redis;

final readonly class RedisRateRepository implements RateRepositoryInterface
{
    private const string DATE_FORMAT = 'Y-m-d';
    private const string KEY_PREFIX = 'rate:';

    public function __construct(private Redis $redis)
    {
    }

    /**
     * @param DateTimeImmutable[] $dates
     * @param string[] $currencyCodes
     */
    public function getRates(array $dates, array $currencyCodes): RateCollection
    {
        $keys = $this->buildKeys($dates, $currencyCodes);
        $values = $this->redis->mget($keys);

        return $this->mapValuesToRates($values);
    }

    public function save(RateCollection $rates): void
    {
        $this->redis->mset($this->buildKeyValuePairs($rates));
    }

    /**
     * @param DateTimeImmutable[] $dates
     * @param string[] $currencyCodes
     * @return string[]
     */
    private function buildKeys(array $dates, array $currencyCodes): array
    {
        $keys = [];

        foreach ($dates as $date) {
            foreach ($currencyCodes as $code) {
                $keys[] = $this->buildKey($date, $code);
            }
        }

        return $keys;
    }

    private function buildKey(DateTimeImmutable $date, string $code): string
    {
        return self::KEY_PREFIX . $date->format(self::DATE_FORMAT) . ':' . $code;
    }

    private function mapValuesToRates(array $values): RateCollection
    {
        $rates = [];

        foreach ($values as $value) {
            if ($value !== false) {
                $rates[] = $this->mapValueToRate($value);
            }
        }

        return new RateCollection(...$rates);
    }

    private function mapValueToRate(string $value): Rate
    {
        $data = json_decode($value, true);

        return new Rate(
            date: new DateTimeImmutable($data['date']),
            effectiveDate: new DateTimeImmutable($data['effective_date']),
            currency: new Currency(
                code: $data['currency_code'],
                name: $data['currency_name'],
            ),
            nominal: $data['nominal'],
            value: $data['value'],
        );
    }

    private function buildKeyValuePairs(RateCollection $rates): array
    {
        $pairs = [];

        foreach ($rates as $rate) {
            $key = $this->buildKey($rate->date, $rate->currency->code);
            $value = $this->encodeRateToJson($rate);

            $pairs[$key] = $value;
        }

        return $pairs;
    }

    private function encodeRateToJson(Rate $rate): string
    {
        return json_encode([
            'date' => $rate->date->format(self::DATE_FORMAT),
            'effective_date' => $rate->effectiveDate->format(self::DATE_FORMAT),
            'currency_code' => $rate->currency->code,
            'currency_name' => $rate->currency->name,
            'nominal' => $rate->nominal,
            'value' => $rate->value,
        ]);
    }
}
