<?php

declare(strict_types=1);

namespace Nitro\Providers;

use Nitro\Auth\AuthManager;
use Nitro\Auth\DatabaseUserProvider;
use Nitro\Auth\Session;
use Nitro\Auth\UserProvider;
use Nitro\Database\DatabaseManager;
use Nitro\Events\Dispatcher;

/**
 * Registers the authentication stack.
 */
final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Session::class);

        $this->app->singleton(UserProvider::class, static function ($app): UserProvider {
            $config = $app->make('config');

            return new DatabaseUserProvider(
                $app->make(DatabaseManager::class)->connection(),
                (string) $config->get('auth.table', 'users')
            );
        });

        $this->app->singleton(AuthManager::class, static fn ($app): AuthManager => new AuthManager(
            $app->make(Session::class),
            $app->make(UserProvider::class),
            $app->make(Dispatcher::class)
        ));

        $this->app->alias(AuthManager::class, 'auth');
    }
}
