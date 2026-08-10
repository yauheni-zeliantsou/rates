<?php

declare(strict_types=1);

namespace Tests\Rate\Controller;

use App\Http\Request;
use App\Rate\Controller\CurrencyController;
use App\Rate\Domain\Entity\Currency;
use App\Rate\Domain\Entity\CurrencyCollection;
use App\Rate\Domain\Interface\CurrencyRepositoryInterface;
use PHPUnit\Framework\TestCase;

final class CurrencyControllerTest extends TestCase
{
    public function testIndexReturnsAllCurrenciesMapped(): void
    {
        $repository = $this->createMock(CurrencyRepositoryInterface::class);
        $repository->expects($this->once())
            ->method('getAll')
            ->willReturn(new CurrencyCollection(
                new Currency(code: 'USD', name: 'Доллар США'),
                new Currency(code: 'EUR', name: 'Евро'),
            ));

        $controller = new CurrencyController($repository);
        $response = $controller->index(new Request('GET', '/api/currencies', []));

        $this->assertSame(200, $response->status());
        $this->assertSame([
            ['code' => 'USD', 'name' => 'Доллар США'],
            ['code' => 'EUR', 'name' => 'Евро'],
        ], $response->data());
    }

    public function testIndexReturnsEmptyArrayWhenNoCurrenciesExist(): void
    {
        $repository = $this->createMock(CurrencyRepositoryInterface::class);
        $repository->expects($this->once())->method('getAll')->willReturn(new CurrencyCollection());

        $controller = new CurrencyController($repository);
        $response = $controller->index(new Request('GET', '/api/currencies', []));

        $this->assertSame(200, $response->status());
        $this->assertSame([], $response->data());
    }
}
