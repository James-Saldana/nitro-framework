<?php

declare(strict_types=1);

namespace Nitro\Middleware;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Adds CORS headers and short-circuits preflight requests.
 */
final class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly string $allowOrigin = '*',
        private readonly string $allowMethods = 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
        private readonly string $allowHeaders = 'Content-Type, Authorization, X-Requested-With'
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return $this->withHeaders(new Response(204))
                ->withHeader('Access-Control-Max-Age', '86400');
        }

        return $this->withHeaders($handler->handle($request));
    }

    private function withHeaders(ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withHeader('Access-Control-Allow-Origin', $this->allowOrigin)
            ->withHeader('Access-Control-Allow-Methods', $this->allowMethods)
            ->withHeader('Access-Control-Allow-Headers', $this->allowHeaders);
    }
}
