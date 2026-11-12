<?php

declare(strict_types=1);

namespace Nitro\Middleware;

use Nitro\Auth\AuthManager;
use Nitro\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Route middleware for guest-only pages (login, registration).
 */
final class GuestMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly ResponseFactory $responses
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->auth->guest()) {
            return $handler->handle($request);
        }

        return $this->responses->redirect('/dashboard');
    }
}
