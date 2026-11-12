<?php

declare(strict_types=1);

namespace Nitro\Exceptions;

use Nitro\Container\Container;
use Nitro\Http\ResponseFactory;
use Nitro\Support\Str;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Centralised exception rendering: JSON for API expectations,
 * a minimal error view otherwise.
 */
final class Handler
{
    public function __construct(
        private readonly Container $container,
        private readonly ResponseFactory $responseFactory
    ) {
    }

    public function render(ServerRequestInterface $request, Throwable $e): ResponseInterface
    {
        $status = $e instanceof HttpException ? $e->getStatusCode() : 500;
        $debug = (bool) $this->container->has('config') && $this->container->make('config')->get('app.debug', false);

        if ($this->wantsJson($request)) {
            $payload = [
                'error' => [
                    'status' => $status,
                    'message' => $debug ? $e->getMessage() : ($status >= 500 ? 'Internal Server Error' : $e->getMessage()),
                ],
            ];

            if ($debug) {
                $payload['error']['exception'] = get_class($e);
                $payload['error']['file'] = $e->getFile();
                $payload['error']['line'] = $e->getLine();
                $payload['error']['trace'] = explode("\n", $e->getTraceAsString());
            }

            return $this->responseFactory->json($payload, $status);
        }

        $body = sprintf(
            '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>%1$d Error</title></head>'
            . '<body><h1>%1$d &mdash; %2$s</h1>%3$s</body></html>',
            $status,
            htmlspecialchars($debug ? $e->getMessage() : $this->reason($status), ENT_QUOTES),
            $debug ? '<pre>' . htmlspecialchars($e->getTraceAsString(), ENT_QUOTES) . '</pre>' : ''
        );

        return $this->responseFactory->html($body, $status);
    }

    private function wantsJson(ServerRequestInterface $request): bool
    {
        $accept = $request->getHeaderLine('Accept');
        $path = $request->getUri()->getPath();

        return Str::startsWith($accept, 'application/json')
            || str_contains($accept, 'application/json')
            || Str::startsWith($path, '/api/');
    }

    private function reason(int $status): string
    {
        return match ($status) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            419 => 'Page Expired',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            default => 'Internal Server Error',
        };
    }
}
