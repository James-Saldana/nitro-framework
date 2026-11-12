<?php

declare(strict_types=1);

namespace Nitro\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Parses JSON request bodies into the parsed-body slot.
 */
final class JsonBodyParserMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $contentType = $request->getHeaderLine('Content-Type');

        if (! str_contains($contentType, 'application/json')) {
            return $handler->handle($request);
        }

        $raw = (string) $request->getBody();

        if ($raw === '') {
            return $handler->handle($request);
        }

        $parsed = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($parsed)) {
            $parsed = [];
        }

        return $handler->handle($request->withParsedBody($parsed));
    }
}
