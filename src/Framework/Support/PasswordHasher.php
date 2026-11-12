<?php

declare(strict_types=1);

namespace Nitro\Support;

use RuntimeException;

/**
 * Password hashing helpers built on PHP's native algorithm.
 */
final class PasswordHasher
{
    private const ALGORITHM = PASSWORD_BCRYPT;

    public static function hash(string $password): string
    {
        $hash = password_hash($password, self::ALGORITHM);

        if ($hash === false) {
            throw new RuntimeException('Unable to hash password.');
        }

        return $hash;
    }

    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, self::ALGORITHM);
    }
}
