<?php

declare(strict_types=1);

namespace Nitro\Http;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Helpers for extracting typed input from a PSR-7 server request.
 */
final class Request
{
    /** @return array<string, mixed> */
    public static function all(ServerRequestInterface $request): array
    {
        $body = (array) ($request->getParsedBody() ?? []);
        $query = $request->getQueryParams();

        return array_merge($query, $body);
    }

    public static function input(ServerRequestInterface $request, string $key, mixed $default = null): mixed
    {
        return self::all($request)[$key] ?? $default;
    }

    public static function string(ServerRequestInterface $request, string $key, string $default = ''): string
    {
        $value = self::input($request, $key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    public static function integer(ServerRequestInterface $request, string $key, int $default = 0): int
    {
        $value = self::input($request, $key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    public static function boolean(ServerRequestInterface $request, string $key, bool $default = false): bool
    {
        $value = self::input($request, $key, $default);

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function method(ServerRequestInterface $request): string
    {
        return strtoupper($request->getMethod());
    }

    /**
     * Resolve the effective method, honouring the _method override used by
     * HTML forms for PUT/PATCH/DELETE.
     */
    public static function effectiveMethod(ServerRequestInterface $request): string
    {
        $method = self::method($request);

        if ($method === 'POST') {
            $override = strtoupper(self::string($request, '_method'));

            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $override;
            }
        }

        return $method;
    }

    public static function isJson(ServerRequestInterface $request): bool
    {
        return str_contains($request->getHeaderLine('Content-Type'), 'application/json');
    }

    public static function ip(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();

        return $server['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
