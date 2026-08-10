<?php

declare(strict_types=1);

namespace Tests\Rate\Domain\Service;

use App\Rate\Domain\Entity\RateCollection;
use App\Rate\Domain\Interface\RateRepositoryInterface;
use App\Rate\Domain\Interface\RateSourceInterface;
use App\Rate\Domain\Service\RateService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Rate\Support\RateTestFactoryTrait;

final class RateServiceTest extends TestCase
{
    use RateTestFactoryTrait;

    public function testGetRatesReturnsStorageRatesWhenNothingIsMissing(): void
    {
        $dates = [new DateTimeImmutable('2026-08-09')];
        $currencyCodes = ['USD'];
        $storageRates = new RateCollection($this->createRate('2026-08-09', 'USD'));

        $source = $this->createMock(RateSourceInterface::class);
        $source->expects($this->never())->method('getRates');

        $repository = $this->createMock(RateRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('getRates')
            ->with($dates, $currencyCodes)
            ->willReturn($storageRates);
        $repository->expects($this->never())->method('save');

        $service = new RateService($source, $repository);
        $result = $service->getRates($dates, $currencyCodes);

        $this->assertEquals($storageRates, $result);
    }

    public function testGetRatesFetchesOnlyMissingDatesFromSourceAndSaves(): void
    {
        $dateFrom = new DateTimeImmutable('2026-08-09');
        $dateTo = new DateTimeImmutable('2026-08-10');
        $currencyCodes = ['USD'];

        $storageRates = new RateCollection($this->createRate('2026-08-09', 'USD'));
        $fetchedRates = new RateCollection($this->createRate('2026-08-10', 'USD'));

        $source = $this->createMock(RateSourceInterface::class);
        $source->expects($this->once())
            ->method('getRates')
            ->with([$dateTo])
            ->willReturn($fetchedRates);

        $repository = $this->createMock(RateRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('getRates')
            ->with([$dateFrom, $dateTo], $currencyCodes)
            ->willReturn($storageRates);
        $repository->expects($this->once())->method('save')->with($fetchedRates);

        $service = new RateService($source, $repository);
        $result = $service->getRates([$dateFrom, $dateTo], $currencyCodes);

        $expected = $storageRates->merged($fetchedRates)->filteredByCurrencyCodes($currencyCodes);
        $this->assertEquals($expected, $result);
    }

    public function testGetRatesFetchesAllDatesWhenStorageIsEmpty(): void
    {
        $dates = [new DateTimeImmutable('2026-08-09')];
        $currencyCodes = ['USD'];
        $fetchedRates = new RateCollection($this->createRate('2026-08-09', 'USD'));

        $source = $this->createMock(RateSourceInterface::class);
        $source->expects($this->once())->method('getRates')->with($dates)->willReturn($fetchedRates);

        $repository = $this->createMock(RateRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('getRates')
            ->with($dates, $currencyCodes)
            ->willReturn(new RateCollection());
        $repository->expects($this->once())->method('save')->with($fetchedRates);

        $service = new RateService($source, $repository);
        $result = $service->getRates($dates, $currencyCodes);

        $expected = (new RateCollection())->merged($fetchedRates)->filteredByCurrencyCodes($currencyCodes);
        $this->assertEquals($expected, $result);
    }

    public function testIngestFetchesFromSourceAndSavesWithoutReadingFromRepository(): void
    {
        $dates = [new DateTimeImmutable('2026-08-09')];
        $fetchedRates = new RateCollection($this->createRate('2026-08-09', 'USD'));

        $source = $this->createMock(RateSourceInterface::class);
        $source->expects($this->once())->method('getRates')->with($dates)->willReturn($fetchedRates);

        $repository = $this->createMock(RateRepositoryInterface::class);
        $repository->expects($this->never())->method('getRates');
        $repository->expects($this->once())->method('save')->with($fetchedRates);

        $service = new RateService($source, $repository);
        $service->ingest($dates);
    }
}
