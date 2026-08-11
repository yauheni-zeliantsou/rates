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
use App\Support\Logging\ErrorLogger;

$logger = new ErrorLogger();

$redis = (new RedisConnection())->redis();

$source = new CbrClient(new CurlHttpClient(), new CbrXmlParser());
$repository = new RedisRateRepository($redis);

$rateService = new RateService($source, $repository);

try {
    $rateService->ingest([new DateTimeImmutable('tomorrow')]);
} catch (UpstreamUnavailableException $exception) {
    $logger->warning('Failed to fetch rates from CBR: ' . $exception->getMessage());
    fwrite(STDERR, 'Failed to fetch rates from CBR: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
} catch (Throwable $exception) {
    $logger->error($exception->getMessage(), [
        'exception' => $exception::class,
        'file' => $exception->getFile() . ':' . $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
    ]);
    fwrite(STDERR, 'Unexpected error while fetching rates: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
