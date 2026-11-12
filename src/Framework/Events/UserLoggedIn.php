<?php

declare(strict_types=1);

namespace Nitro\Events;

/**
 * Fired after a user successfully authenticates.
 */
final class UserLoggedIn extends Event
{
    public function __construct(public readonly string|int $userId)
    {
    }
}
