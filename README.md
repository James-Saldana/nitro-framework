# Nitro Framework

Nitro is a production-grade micro-framework for PHP 8.3+, built around the PHP-FIG standards it complies with:

| Standard | Implementation |
| --- | --- |
| PSR-4 | Autoloaded under `Nitro\` and `App\` |
| PSR-11 | `Nitro\Container\Container` (autowiring, singletons, contextual bindings, tags) |
| PSR-14 | `Nitro\Events\Dispatcher` + `Nitro\Events\ListenerProvider` |
| PSR-15 | `Application` implements `RequestHandlerInterface`; middleware implements `MiddlewareInterface` |
| PSR-7 | Request/response handling via `guzzlehttp/psr7` |

## Features

- **Dependency Injection Container** with constructor autowiring, aliases, singletons, tagged services and contextual (`when/needs/give`) bindings.
- **Routing** with dynamic parameters and regex constraints, route names, RESTful resources, route groups and route-specific middleware.
- **Middleware** at global and route level through a PSR-15 pipeline; ships with auth, guest, CORS and JSON body parser middleware.
- **Service Providers** with a two-phase lifecycle (`register`, then `boot`).
- **Database** layer built on PDO: connection manager, transactions, query log, and a fluent query builder with pagination and safe identifier quoting.
- **Authentication**: session guard, database user provider, password hashing, login/logout events.
- **Events**: PSR-14 dispatcher with wildcard listeners, stoppable propagation and subscribers.
- **Views**: minimal PHP template engine with layouts, sections and automatic escaping.

## Getting started

```bash
composer install
php -S localhost:8000 -t public
```

Run the test suite:

```bash
composer test
```

## Application structure

```
bootstrap/app.php        Application bootstrap
config/                  Configuration files
database/migrations/     Migrations
public/index.php         HTTP entry point
resources/views/         Templates
routes/web.php           Route definitions
src/Framework/           The framework (namespace Nitro\)
src/App/                 Example application (namespace App\)
tests/                   PHPUnit test suite
```

## Usage

```php
use Nitro\Routing\Router;

// In routes/web.php:
return static function (Router $router): void {
    $router->get('/', [HomeController::class, 'index'])->name('home');

    $router->group(['prefix' => '/admin', 'middleware' => AuthMiddleware::class],
        static function (Router $r): void {
            $r->get('/stats', [StatsController::class, 'index']);
        });
};
```

## License

MIT. See [LICENSE](LICENSE).
