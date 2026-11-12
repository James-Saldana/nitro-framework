<?php

declare(strict_types=1);

namespace Nitro\Providers;

use Nitro\Http\ResponseFactory;
use Nitro\View\View;
use Nitro\View\ViewEngine;

/**
 * Registers the view engine and response factory.
 */
final class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ResponseFactory::class);
        $this->app->alias(ResponseFactory::class, 'response');

        $this->app->singleton(ViewEngine::class, static function ($app): ViewEngine {
            $config = $app->make('config');

            return new ViewEngine(
                (string) $config->get('view.paths.0', (string) $app->make('path.resources') . '/views'),
                (string) $config->get('view.cache', (string) $app->make('path.storage') . '/cache')
            );
        });

        $this->app->singleton(View::class, static fn ($app): View => new View(
            $app->make(ViewEngine::class),
            $app->make(ResponseFactory::class)
        ));

        $this->app->alias(View::class, 'view');
    }
}
