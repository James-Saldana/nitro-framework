<?php

declare(strict_types=1);

namespace Nitro\Auth;

/**
 * Contract for user objects consumable by the authentication system.
 */
interface Authenticatable
{
    public function getAuthIdentifier(): string|int;

    public function getAuthPassword(): string;
}
