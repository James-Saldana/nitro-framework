<?php

declare(strict_types=1);

namespace Nitro\Http;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Convenience factory for PSR-7 responses.
 */
final class ResponseFactory
{
    public function make(
        int $status = 200,
        array $headers = [],
        string|StreamInterface|null $body = null
    ): ResponseInterface {
        $stream = $body instanceof StreamInterface
            ? $body
            : Utils::streamFor($body ?? '');

        return new Response($status, $headers, $stream);
    }

    /** @param array<mixed> $payload */
    public function json(array $payload, int $status = 200, array $headers = []): ResponseInterface
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';

        return $this->make($status, $headers, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    public function html(string $body, int $status = 200, array $headers = []): ResponseInterface
    {
        $headers['Content-Type'] ??= 'text/html; charset=utf-8';

        return $this->make($status, $headers, $body);
    }

    public function text(string $body, int $status = 200, array $headers = []): ResponseInterface
    {
        $headers['Content-Type'] ??= 'text/plain; charset=utf-8';

        return $this->make($status, $headers, $body);
    }

    public function noContent(): ResponseInterface
    {
        return $this->make(204);
    }

    public function redirect(string $location, int $status = 302): ResponseInterface
    {
        return $this->make($status, ['Location' => $location]);
    }

    public function download(string $content, string $filename, string $contentType = 'application/octet-stream'): ResponseInterface
    {
        return $this->make(200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => sprintf('attachment; filename="%s"', addslashes($filename)),
        ], $content);
    }
}
