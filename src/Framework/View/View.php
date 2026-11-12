<?php

declare(strict_types=1);

namespace Nitro\View;

use Nitro\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;

/**
 * Renders views through the engine and wraps them in PSR-7 responses.
 */
final class View
{
    public function __construct(
        private readonly ViewEngine $engine,
        private readonly ResponseFactory $responses
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = []): string
    {
        return $this->engine->render($template, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function make(string $template, array $data = [], int $status = 200): ResponseInterface
    {
        return $this->responses->html($this->render($template, $data), $status);
    }
}
