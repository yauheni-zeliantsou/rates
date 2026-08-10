<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Rate\Domain\Service\RateService;
use App\Rate\Infrastructure\CentralBankOfRussia\CbrClient;
use App\Rate\Infrastructure\CentralBankOfRussia\CbrXmlParser;
use App\Rate\Infrastructure\Repository\RedisRateRepository;
use App\Support\Cache\RedisConnection;
use App\Support\Exception\UpstreamUnavailableException;
use App\Support\Http\CurlHttpClient;

$redis = (new RedisConnection())->redis();

$source = new CbrClient(new CurlHttpClient(), new CbrXmlParser());
$repository = new RedisRateRepository($redis);

$rateService = new RateService($source, $repository);

try {
    $rateService->ingest([new DateTimeImmutable('tomorrow')]);
} catch (UpstreamUnavailableException $exception) {
    fwrite(STDERR, 'Failed to fetch rates from CBR: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
