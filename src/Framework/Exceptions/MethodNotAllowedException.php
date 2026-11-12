<?php

declare(strict_types=1);

namespace Nitro\Exceptions;

/**
 * Thrown when a route matches but the HTTP method is not allowed.
 */
final class MethodNotAllowedException extends HttpException
{
    /** @param array<int, string> $allowed */
    public function __construct(private readonly array $allowed = [], string $message = 'Method Not Allowed')
    {
        parent::__construct(405, $message);
    }

    /** @return array<int, string> */
    public function getAllowedMethods(): array
    {
        return $this->allowed;
    }
}
