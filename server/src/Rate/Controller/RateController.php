<?php

declare(strict_types=1);

namespace App\Rate\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Rate\Domain\Entity\RateCollection;
use App\Rate\Domain\Interface\CurrencyRepositoryInterface;
use App\Rate\Domain\Service\RateService;
use DateMalformedStringException;
use DateTimeImmutable;
use InvalidArgumentException;

final readonly class RateController
{
    private const int MIN_DAYS = 1;
    private const int MAX_DAYS = 10;
    private const int DEFAULT_DAYS = 10;

    public function __construct(
        private RateService $rateService,
        private CurrencyRepositoryInterface $currencyRepository,
    ) {
    }

    /**
     * @throws DateMalformedStringException
     */
    public function index(Request $request): Response
    {
        $date = $this->parseDate($request->query('date'));
        $daysBeforeDate = $this->parseDays($request->query('days'));
        $currencyCodes = $this->resolveCurrencyCodes($request->query('currencies'));

        $rates = $this->rateService->getRatesForRange($date, $daysBeforeDate, $currencyCodes);

        return Response::json($this->mapRatesToArray($rates));
    }

    private function parseDate(?string $value): DateTimeImmutable
    {
        if ($value === null) {
            return new DateTimeImmutable('today');
        }

        try {
            return new DateTimeImmutable($value);
        } catch (DateMalformedStringException) {
            throw new InvalidArgumentException('Invalid date format for parameter: date');
        }
    }

    private function parseDays(?string $value): int
    {
        if ($value === null) {
            return self::DEFAULT_DAYS;
        }

        if (!ctype_digit($value) || (int) $value < self::MIN_DAYS || (int) $value > self::MAX_DAYS) {
            throw new InvalidArgumentException(
                sprintf('Parameter days must be an integer between %d and %d', self::MIN_DAYS, self::MAX_DAYS)
            );
        }

        return (int) $value;
    }

    /**
     * @return string[]
     */
    private function resolveCurrencyCodes(?string $value): array
    {
        if ($value === null) {
            return $this->getAllCurrencyCodes();
        }

        return explode(',', $value);
    }

    /**
     * @return string[]
     */
    private function getAllCurrencyCodes(): array
    {
        $codes = [];

        foreach ($this->currencyRepository->getAll() as $currency) {
            $codes[] = $currency->code;
        }

        return $codes;
    }

    private function mapRatesToArray(RateCollection $rates): array
    {
        $result = [];

        foreach ($rates as $rate) {
            $result[] = [
                'date' => $rate->date->format('Y-m-d'),
                'effective_date' => $rate->effectiveDate->format('Y-m-d'),
                'currency_code' => $rate->currency->code,
                'currency_name' => $rate->currency->name,
                'nominal' => $rate->nominal,
                'value' => $rate->value,
            ];
        }

        return $result;
    }
}
