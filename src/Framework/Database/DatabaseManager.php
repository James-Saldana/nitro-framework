<?php

declare(strict_types=1);

namespace Nitro\Database;

use Nitro\Config\Repository;

/**
 * Resolves named database connections from configuration.
 */
final class DatabaseManager
{
    /** @var array<string, Connection> */
    private array $connections = [];

    public function __construct(private readonly Repository $config)
    {
    }

    public function connection(string $name = null): Connection
    {
        $name ??= (string) $this->config->get('database.default', 'default');

        return $this->connections[$name] ??= new Connection(
            (array) $this->config->get(sprintf('database.connections.%s', $name), [])
        );
    }

    public function table(string $table, string $connection = null): QueryBuilder
    {
        return new QueryBuilder($this->connection($connection), $table);
    }

    /** @return array<string, Connection> */
    public function openConnections(): array
    {
        return $this->connections;
    }

    public function disconnect(string $name = null): void
    {
        if ($name === null) {
            foreach ($this->connections as $connection) {
                $connection->disconnect();
            }

            $this->connections = [];

            return;
        }

        $this->connections[$name]?->disconnect();
        unset($this->connections[$name]);
    }
}
