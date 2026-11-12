<?php

declare(strict_types=1);

namespace Nitro\Support;

/**
 * Environment variable access with type coercion.
 */
final class Env
{
    private static ?string $path = null;

    /** @var array<string, string|false> */
    private static array $parsed = [];

    public static function load(string $path): void
    {
        self::$path = $path;

        if (! is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $separator = strpos($line, '=');

            if ($separator === false) {
                continue;
            }

            $name = trim(substr($line, 0, $separator));
            $value = trim(substr($line, $separator + 1));
            $value = trim($value, "\"'");

            self::$parsed[$name] = $value;

            if (getenv($name) === false) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return self::coerce((string) $value);
    }

    public static function reload(): void
    {
        self::$parsed = [];

        if (self::$path !== null) {
            self::load(self::$path);
        }
    }

    private static function coerce(string $value): string|bool|int|float|null
    {
        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            'empty', '(empty)' => '',
            default => is_numeric($value) ? $value + 0 : $value,
        };
    }
}
