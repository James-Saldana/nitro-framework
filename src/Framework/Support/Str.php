<?php

declare(strict_types=1);

namespace Nitro\Support;

use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

/**
 * String manipulation helpers.
 */
final class Str
{
    private const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public static function random(int $length = 32): string
    {
        $bytes = random_bytes($length);
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= self::ALPHABET[ord($bytes[$i]) % strlen(self::ALPHABET)];
        }

        return $result;
    }

    public static function studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }

    public static function snake(string $value, string $delimiter = '_'): string
    {
        $value = preg_replace('/\s+/u', '', ucwords($value)) ?? $value;

        return strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value) ?? $value);
    }

    public static function startsWith(string $haystack, string $needle): bool
    {
        return $needle !== '' && str_starts_with($haystack, $needle);
    }

    public static function endsWith(string $haystack, string $needle): bool
    {
        return $needle !== '' && str_ends_with($haystack, $needle);
    }

    public static function slug(string $value, string $separator = '-'): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', $separator, $value) ?? $value;

        return trim($value, $separator);
    }

    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $limit)) . $end;
    }

    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;

        if ($remainder !== 0) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        if ($decoded === false) {
            throw new RuntimeException('Invalid base64url payload.');
        }

        return $decoded;
    }

    public static function timestamp(string $timezone = 'UTC'): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone($timezone)))->format(DATE_ATOM);
    }
}
