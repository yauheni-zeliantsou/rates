<?php

declare(strict_types=1);

namespace Tests\Rate\Controller;

use App\Http\Request;
use App\Rate\Controller\RateController;
use App\Rate\Domain\Entity\Currency;
use App\Rate\Domain\Entity\CurrencyCollection;
use App\Rate\Domain\Entity\RateCollection;
use App\Rate\Domain\Interface\CurrencyRepositoryInterface;
use App\Rate\Domain\Interface\RateRepositoryInterface;
use App\Rate\Domain\Interface\RateSourceInterface;
use App\Rate\Domain\Service\RateService;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Rate\Support\RateTestFactoryTrait;

final class RateControllerTest extends TestCase
{
    use RateTestFactoryTrait;

    public function testIndexReturnsMappedRatesForRequestedCurrenciesDateAndDays(): void
    {
        $request = new Request('GET', '/api/rates', [
            'date' => '2026-08-10',
            'days' => '2',
            'currencies' => 'USD,EUR',
        ]);

        $rates = new RateCollection(
            $this->createRate('2026-08-10', 'USD'),
            $this->createRate('2026-08-09', 'EUR'),
        );

        $rateRepository = $this->createMock(RateRepositoryInterface::class);
        $rateRepository->expects($this->once())
            ->method('getRates')
            ->with(
                [new DateTimeImmutable('2026-08-10'), new DateTimeImmutable('2026-08-09')],
                ['USD', 'EUR'],
            )
            ->willReturn($rates);

        $currencyRepository = $this->createStub(CurrencyRepositoryInterface::class);
        $source = $this->createStub(RateSourceInterface::class);

        $controller = $this->createRateController(
            source: $source,
            repository: $rateRepository,
            currencyRepository: $currencyRepository,
        );
        $response = $controller->index($request);

        $this->assertSame(200, $response->status());
        $this->assertSame([
            [
                'date' => '2026-08-10',
                'effective_date' => '2026-08-10',
                'currency_code' => 'USD',
                'currency_name' => 'USD',
                'nominal' => 1,
                'value' => 1.0,
            ],
            [
                'date' => '2026-08-09',
                'effective_date' => '2026-08-09',
                'currency_code' => 'EUR',
                'currency_name' => 'EUR',
                'nominal' => 1,
                'value' => 1.0,
            ],
        ], $response->data());
    }

    public function testIndexUsesAllCurrenciesWhenCurrenciesParamIsMissing(): void
    {
        $request = new Request('GET', '/api/rates', ['date' => '2026-08-10', 'days' => '1']);

        $repository = $this->createMock(RateRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('getRates')
            ->with([new DateTimeImmutable('2026-08-10')], ['USD', 'EUR'])
            ->willReturn(new RateCollection($this->createRate('2026-08-10', 'USD')));

        $currencyRepository = $this->createMock(CurrencyRepositoryInterface::class);
        $currencyRepository->expects($this->once())
            ->method('getAll')
            ->willReturn(new CurrencyCollection(
                new Currency(code: 'USD', name: 'Доллар США'),
                new Currency(code: 'EUR', name: 'Евро'),
            ));

        $controller = $this->createRateController(repository: $repository, currencyRepository: $currencyRepository);
        $controller->index($request);
    }

    public function testIndexUsesTodayAsDefaultDateWhenDateParamIsMissing(): void
    {
        $today = new DateTimeImmutable('today');
        $request = new Request('GET', '/api/rates', ['days' => '1', 'currencies' => 'USD']);

        $repository = $this->createMock(RateRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('getRates')
            ->with([$today], ['USD'])
            ->willReturn(new RateCollection($this->createRate($today->format('Y-m-d'), 'USD')));

        $controller = $this->createRateController(repository: $repository);
        $controller->index($request);
    }

    public function testIndexUsesDefaultDaysWhenDaysParamIsMissing(): void
    {
        $request = new Request('GET', '/api/rates', ['date' => '2026-08-10', 'currencies' => 'USD']);

        $expectedDates = [];
        $ratesForExpectedDates = [];

        for ($offset = 0; $offset < 10; $offset++) {
            $date = (new DateTimeImmutable('2026-08-10'))->modify("-{$offset} day");
            $expectedDates[] = $date;
            $ratesForExpectedDates[] = $this->createRate($date->format('Y-m-d'), 'USD');
        }

        $repository = $this->createMock(RateRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('getRates')
            ->with($expectedDates, ['USD'])
            ->willReturn(new RateCollection(...$ratesForExpectedDates));

        $controller = $this->createRateController(repository: $repository);
        $controller->index($request);
    }

    public function testIndexThrowsWhenDateFormatIsInvalid(): void
    {
        $request = new Request('GET', '/api/rates', ['date' => 'not-a-date']);

        $this->expectException(InvalidArgumentException::class);

        $this->createRateController()->index($request);
    }

    public function testIndexThrowsWhenDaysIsNotAnInteger(): void
    {
        $request = new Request('GET', '/api/rates', ['days' => 'abc']);

        $this->expectException(InvalidArgumentException::class);

        $this->createRateController()->index($request);
    }

    public function testIndexThrowsWhenDaysIsBelowMinimum(): void
    {
        $request = new Request('GET', '/api/rates', ['days' => '0']);

        $this->expectException(InvalidArgumentException::class);

        $this->createRateController()->index($request);
    }

    public function testIndexThrowsWhenDaysExceedsMaximum(): void
    {
        $request = new Request('GET', '/api/rates', ['days' => '11']);

        $this->expectException(InvalidArgumentException::class);

        $this->createRateController()->index($request);
    }

    private function createRateController(
        ?RateSourceInterface $source = null,
        ?RateRepositoryInterface $repository = null,
        ?CurrencyRepositoryInterface $currencyRepository = null,
    ): RateController {
        $source ??= $this->createStub(RateSourceInterface::class);
        $repository ??= $this->createStub(RateRepositoryInterface::class);
        $currencyRepository ??= $this->createStub(CurrencyRepositoryInterface::class);

        return new RateController(new RateService($source, $repository), $currencyRepository);
    }
}
