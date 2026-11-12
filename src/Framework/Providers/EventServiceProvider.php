<?php

declare(strict_types=1);

namespace Nitro\Providers;

use Nitro\Events\Dispatcher;
use Nitro\Events\ListenerProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * Registers the PSR-14 event dispatcher and listener provider.
 */
final class EventServiceProvider extends ServiceProvider
{
    /** @var array<string, callable|string> */
    protected array $listen = [];

    /** @var array<int, string> */
    protected array $subscribers = [];

    public function register(): void
    {
        $this->app->singleton(ListenerProvider::class);
        $this->app->instance(ListenerProviderInterface::class, $this->app->make(ListenerProvider::class));

        $this->app->singleton(Dispatcher::class, static fn ($app): Dispatcher => new Dispatcher($app->make(ListenerProviderInterface::class)));
        $this->app->instance(EventDispatcherInterface::class, $this->app->make(Dispatcher::class));

        $this->app->alias(Dispatcher::class, 'events');
    }

    public function boot(): void
    {
        $provider = $this->app->make(ListenerProvider::class);

        foreach ($this->listen as $event => $listener) {
            $provider->listen($event, $listener);
        }

        foreach ($this->subscribers as $subscriber) {
            $provider->subscribe($subscriber);
        }
    }
}
