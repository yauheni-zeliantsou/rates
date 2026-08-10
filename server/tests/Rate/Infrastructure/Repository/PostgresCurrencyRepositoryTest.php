<?php

declare(strict_types=1);

namespace Tests\Rate\Infrastructure\Repository;

use App\Rate\Domain\Entity\Currency;
use App\Rate\Domain\Entity\CurrencyCollection;
use App\Rate\Infrastructure\Repository\PostgresCurrencyRepository;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class PostgresCurrencyRepositoryTest extends TestCase
{
    public function testGetAllQueriesCurrenciesTable(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->once())->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('query')
            ->with('SELECT code, name FROM currencies')
            ->willReturn($statement);

        $repository = new PostgresCurrencyRepository($pdo);
        $repository->getAll();
    }

    public function testGetAllReturnsCurrenciesMappedFromRows(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->once())
            ->method('fetchAll')
            ->with(PDO::FETCH_ASSOC)
            ->willReturn([
                ['code' => 'USD', 'name' => 'Доллар США'],
                ['code' => 'EUR', 'name' => 'Евро'],
            ]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('query')->willReturn($statement);

        $repository = new PostgresCurrencyRepository($pdo);
        $currencyCollection = $repository->getAll();

        $this->assertCount(2, $currencyCollection);

        $currencies = iterator_to_array($currencyCollection, false);

        $this->assertSame('USD', $currencies[0]->code);
        $this->assertSame('Доллар США', $currencies[0]->name);
        $this->assertSame('EUR', $currencies[1]->code);
        $this->assertSame('Евро', $currencies[1]->name);
    }

    public function testGetAllReturnsEmptyCollectionWhenNoRows(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->once())->method('fetchAll')->willReturn([]);

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('query')->willReturn($statement);

        $repository = new PostgresCurrencyRepository($pdo);
        $currencies = $repository->getAll();

        $this->assertCount(0, $currencies);
    }

    public function testSaveUpsertsEachCurrencyInCollection(): void
    {
        $currencies = new CurrencyCollection(
            new Currency(code: 'USD', name: 'Доллар США'),
            new Currency(code: 'EUR', name: 'Евро'),
        );

        $executedParams = [];

        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->exactly(2))
            ->method('execute')
            ->willReturnCallback(function (array $params) use (&$executedParams): bool {
                $executedParams[] = $params;

                return true;
            });

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('ON CONFLICT (code) DO UPDATE'))
            ->willReturn($statement);

        $repository = new PostgresCurrencyRepository($pdo);
        $repository->save($currencies);

        $this->assertSame([
            ['code' => 'USD', 'name' => 'Доллар США'],
            ['code' => 'EUR', 'name' => 'Евро'],
        ], $executedParams);
    }

    public function testSaveDoesNothingForEmptyCollection(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->expects($this->never())->method('execute');

        $pdo = $this->createMock(PDO::class);
        $pdo->expects($this->once())->method('prepare')->willReturn($statement);

        $repository = new PostgresCurrencyRepository($pdo);
        $repository->save(new CurrencyCollection());
    }
}
