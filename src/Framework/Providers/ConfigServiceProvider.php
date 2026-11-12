<?php

declare(strict_types=1);

namespace Nitro\Providers;

use Nitro\Config\Repository;
use Nitro\Support\Env;

/**
 * Loads configuration files and the environment into the container.
 */
final class ConfigServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Env::load((string) $this->app->make('path.env'));

        $config = new Repository($this->loadConfigFiles((string) $this->app->make('path.config')));

        $this->app->instance('config', $config);
        $this->app->instance(Repository::class, $config);
    }

    /** @return array<string, array<string, mixed>> */
    private function loadConfigFiles(string $directory): array
    {
        $items = [];

        foreach (glob(rtrim($directory, '/') . '/*.php') ?: [] as $file) {
            $key = basename($file, '.php');
            $items[$key] = (array) require $file;
        }

        return $items;
    }
}
