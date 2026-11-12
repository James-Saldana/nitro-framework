<?php

declare(strict_types=1);

namespace App\Providers;

use Nitro\Providers\RouteServiceProvider;
use Nitro\Providers\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        // Application-wide event listeners, view composers and macros
        // can be registered here.
    }
}
