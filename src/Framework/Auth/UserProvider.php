<?php

declare(strict_types=1);

namespace Nitro\Auth;

/**
 * Retrieves users by credentials for the session guard.
 */
interface UserProvider
{
    /** @param array{email?: string, username?: string} $credentials */
    public function retrieveByCredentials(array $credentials): ?Authenticatable;

    public function retrieveById(string|int $id): ?Authenticatable;

    public function validateCredentials(Authenticatable $user, array $credentials): bool;
}
