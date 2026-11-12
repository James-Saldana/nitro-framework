<?php

declare(strict_types=1);

namespace Nitro\Providers;

use Nitro\Database\DatabaseManager;

/**
 * Registers the database manager and default connection.
 */
final class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DatabaseManager::class, static fn ($app): DatabaseManager => new DatabaseManager($app->make('config')));

        $this->app->alias(DatabaseManager::class, 'db');
    }
}
