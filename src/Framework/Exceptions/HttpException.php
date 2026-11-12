<?php

declare(strict_types=1);

namespace Nitro\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Base exception carrying an HTTP status code, thrown by framework internals.
 */
class HttpException extends RuntimeException
{
    public function __construct(
        private readonly int $status = 500,
        string $message = '',
        Throwable $previous = null
    ) {
        parent::__construct($message !== '' ? $message : 'HTTP error', $status, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->status;
    }
}
