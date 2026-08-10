<?php

declare(strict_types=1);

namespace App\Rate\Infrastructure\Repository;

use App\Rate\Domain\Entity\Currency;
use App\Rate\Domain\Entity\CurrencyCollection;
use App\Rate\Domain\Interface\CurrencyRepositoryInterface;
use PDO;

final readonly class PostgresCurrencyRepository implements CurrencyRepositoryInterface
{
    public function __construct(private PDO $pdo)
    {
    }

    public function getAll(): CurrencyCollection
    {
        $statement = $this->pdo->query('SELECT code, name FROM currencies');
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $this->mapRowsToCurrencies($rows);
    }

    private function mapRowsToCurrencies(array $rows): CurrencyCollection
    {
        $currencies = [];

        foreach ($rows as $row) {
            $currencies[] = new Currency(
                code: $row['code'],
                name: $row['name'],
            );
        }

        return new CurrencyCollection(...$currencies);
    }
}
