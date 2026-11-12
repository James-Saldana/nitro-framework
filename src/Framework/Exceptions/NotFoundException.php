<?php

declare(strict_types=1);

namespace Nitro\Exceptions;

/**
 * Thrown when no route matches the request.
 */
final class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Not Found')
    {
        parent::__construct(404, $message);
    }
}
