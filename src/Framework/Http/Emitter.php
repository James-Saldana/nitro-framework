<?php

declare(strict_types=1);

namespace Nitro\Http;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * Emits a PSR-7 response to the client, following RFC 7230 header rules.
 */
final class Emitter
{
    public function emit(ResponseInterface $response): void
    {
        if (headers_sent()) {
            return;
        }

        http_response_code($response->getStatusCode());

        foreach ($response->getHeaders() as $name => $values) {
            $replace = strtolower($name) !== 'set-cookie';

            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), $replace);
            }
        }

        $body = $response->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        while (! $body->eof()) {
            echo $body->read(8192);
        }
    }

    public static function quick(string $content, int $status = 200, array $headers = []): ResponseInterface
    {
        return new Response($status, $headers, $content);
    }
}
