<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Rate\Domain\Entity\CurrencyCollection;
use App\Rate\Infrastructure\CentralBankOfRussia\CbrXmlParser;
use App\Rate\Infrastructure\Repository\PostgresCurrencyRepository;
use App\Support\Database\PostgresConnection;

$fallbackXmlPath = __DIR__ . '/../resources/fallback-daily.xml';

if (!is_readable($fallbackXmlPath)) {
    fwrite(STDERR, "Fallback file not found or not readable: {$fallbackXmlPath}" . PHP_EOL);
    exit(1);
}

$xml = file_get_contents($fallbackXmlPath);
$today = new DateTimeImmutable('today');

$rates = (new CbrXmlParser())->parse($xml, $today);

$currencies = [];
foreach ($rates as $rate) {
    $currencies[$rate->currency->code] = $rate->currency;
}

$pdo = (new PostgresConnection())->pdo();
(new PostgresCurrencyRepository($pdo))->save(new CurrencyCollection(...array_values($currencies)));

fwrite(STDOUT, sprintf('Seeded %d currencies%s', count($currencies), PHP_EOL));
