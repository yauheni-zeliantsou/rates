<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Auth\Controller\SessionController;
use App\Auth\Controller\TokenController;
use App\Auth\Repository\TokenRepository;
use App\Auth\Repository\UserRepository;
use App\Auth\Service\TokenService;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Rate\Controller\CurrencyController;
use App\Rate\Controller\RateController;
use App\Rate\Domain\Service\RateService;
use App\Rate\Infrastructure\CentralBankOfRussia\CbrClient;
use App\Rate\Infrastructure\CentralBankOfRussia\CbrXmlParser;
use App\Rate\Infrastructure\Repository\PostgresCurrencyRepository;
use App\Rate\Infrastructure\Repository\RedisRateRepository;
use App\Support\Cache\RedisConnection;
use App\Support\Database\PostgresConnection;
use App\Support\Http\CurlHttpClient;
use App\Support\Logging\ErrorLogger;

$logger = new ErrorLogger();

try {
    $pdo = (new PostgresConnection())->pdo();
    $redis = (new RedisConnection())->redis();

    $source = new CbrClient(new CurlHttpClient(), new CbrXmlParser());

    $rateRepository = new RedisRateRepository($redis);
    $currencyRepository = new PostgresCurrencyRepository($pdo);

    $ratesController = new RateController(new RateService($source, $rateRepository), $currencyRepository);
    $currenciesController = new CurrencyController($currencyRepository);

    $tokenService = new TokenService(new TokenRepository($pdo));
    $tokenController = new TokenController(new UserRepository($pdo), $tokenService, getenv('OAUTH_CLIENT_ID'));
    $sessionController = new SessionController($tokenService);
    $authMiddleware = new AuthMiddleware($tokenService);

    $router = new Router($logger);
    $router->post('/oauth/token', [$tokenController, 'issue']);
    $router->get('/auth/verify', [$sessionController, 'verify']);
    $router->get('/api/rates', [$ratesController, 'index'], [$authMiddleware]);
    $router->get('/api/currencies', [$currenciesController, 'index'], [$authMiddleware]);

    $response = $router->dispatch(Request::fromGlobals());
} catch (Throwable $exception) {
    $logger->error($exception->getMessage(), [
        'exception' => $exception::class,
        'file' => $exception->getFile() . ':' . $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
    ]);

    $response = Response::json(['error' => 'Internal Server Error'], 500);
}

$response->send();
