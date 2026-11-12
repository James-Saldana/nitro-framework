<?php

declare(strict_types=1);

namespace Nitro\Auth;

use Nitro\Database\Connection;

/**
 * Database-backed user provider operating on a users table.
 */
final class DatabaseUserProvider implements UserProvider
{
    public function __construct(
        private readonly Connection $connection,
        private readonly string $table = 'users'
    ) {
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        foreach (['email', 'username'] as $column) {
            if (! isset($credentials[$column])) {
                continue;
            }

            $row = $this->connection->first(
                sprintf('SELECT * FROM %s WHERE %s = ? LIMIT 1', $this->table, $column),
                [$credentials[$column]]
            );

            return $row === null ? null : GenericUser::fromRow($row);
        }

        return null;
    }

    public function retrieveById(string|int $id): ?Authenticatable
    {
        $row = $this->connection->first(
            sprintf('SELECT * FROM %s WHERE id = ? LIMIT 1', $this->table),
            [$id]
        );

        return $row === null ? null : GenericUser::fromRow($row);
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $password = (string) ($credentials['password'] ?? '');

        return $password !== '' && PasswordHasher::verify($password, $user->getAuthPassword());
    }
}
