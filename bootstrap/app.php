<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Nitro\Application;
use Nitro\Middleware\CorsMiddleware;
use Nitro\Middleware\JsonBodyParserMiddleware;

require __DIR__ . '/../vendor/autoload.php';

$basePath = dirname(__DIR__);

$app = new Application($basePath);

$app->register([
    AppServiceProvider::class,
]);

$app->middleware([
    new CorsMiddleware(),
    new JsonBodyParserMiddleware(),
]);

return $app;
