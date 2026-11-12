<?php

declare(strict_types=1);

namespace Nitro\Events;

/**
 * Fired when a user logs out.
 */
final class UserLoggedOut extends Event
{
    public function __construct(public readonly string|int $userId)
    {
    }
}
