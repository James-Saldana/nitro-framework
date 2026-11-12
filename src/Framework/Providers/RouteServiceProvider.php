<?php

declare(strict_types=1);

namespace Nitro\Providers;

use Nitro\Routing\Router;

/**
 * Registers the router and invokes the application's route registrar.
 */
final class RouteServiceProvider extends ServiceProvider
{
    /** @var array<int, string> */
    protected array $routeFiles = [];

    public function register(): void
    {
        $this->app->singleton(Router::class);
        $this->app->alias(Router::class, 'router');
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);

        $files = $this->routeFiles !== []
            ? $this->routeFiles
            : [(string) $this->app->make('path.routes') . '/web.php'];

        foreach ($files as $file) {
            if (is_file($file)) {
                $registrar = require $file;
                $registrar($router);
            }
        }
    }
}
