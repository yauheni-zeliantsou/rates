<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Rate\Infrastructure\CentralBankOfRussia\CbrXmlParser;
use App\Rate\Infrastructure\Repository\RedisRateRepository;
use App\Support\Cache\RedisConnection;

$fallbackXmlPath = __DIR__ . '/../resources/fallback-daily.xml';

if (!is_readable($fallbackXmlPath)) {
    fwrite(STDERR, "Fallback file not found or not readable: {$fallbackXmlPath}" . PHP_EOL);
    exit(1);
}

$xml = file_get_contents($fallbackXmlPath);
$today = new DateTimeImmutable('today');

$rates = (new CbrXmlParser())->parse($xml, $today);

$redis = (new RedisConnection())->redis();
(new RedisRateRepository($redis))->save($rates);

fwrite(STDOUT, sprintf('Seeded %d fallback rates for %s%s', count($rates), $today->format('Y-m-d'), PHP_EOL));
