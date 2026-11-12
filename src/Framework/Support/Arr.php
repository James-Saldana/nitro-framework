<?php

declare(strict_types=1);

namespace Nitro\Support;

/**
 * Utilities for nested arrays accessed with dot notation.
 */
final class Arr
{
    /**
     * @param array<string, mixed> $array
     */
    public static function get(array $array, string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (! is_array($array) || ! array_key_exists($segment, $array)) {
                return $default;
            }

            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * @param array<string, mixed> $array
     */
    public static function set(array &$array, string $key, mixed $value): void
    {
        $segments = explode('.', $key);
        $target = &$array;

        foreach ($segments as $segment) {
            if (! isset($target[$segment]) || ! is_array($target[$segment])) {
                $target[$segment] = [];
            }

            $target = &$target[$segment];
        }

        $target = $value;
    }

    /**
     * @param array<string, mixed> $array
     */
    public static function has(array $array, string $key): bool
    {
        $segments = explode('.', $key);
        $target = $array;

        foreach ($segments as $segment) {
            if (! is_array($target) || ! array_key_exists($segment, $target)) {
                return false;
            }

            $target = $target[$segment];
        }

        return true;
    }

    /**
     * @param array<string, mixed> $array
     */
    public static function forget(array &$array, string $key): void
    {
        $segments = explode('.', $key);
        $last = array_pop($segments);
        $target = &$array;

        foreach ($segments as $segment) {
            if (! isset($target[$segment]) || ! is_array($target[$segment])) {
                return;
            }

            $target = &$target[$segment];
        }

        unset($target[$last]);
    }
}
