<?php

declare(strict_types=1);

namespace Nitro\Providers;

use Nitro\Container\Container;

/**
 * Base service provider: register bindings, then bootstrap once all
 * providers have registered.
 */
abstract class ServiceProvider
{
    public function __construct(protected readonly Container $app)
    {
    }

    abstract public function register(): void;

    public function boot(): void
    {
    }
}
