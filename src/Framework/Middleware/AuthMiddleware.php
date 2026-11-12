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
 * Route middleware requiring an authenticated user; redirects guests
 * to the login route.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthManager $auth,
        private readonly ResponseFactory $responses
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->auth->check()) {
            return $handler->handle($request);
        }

        if (str_starts_with($request->getUri()->getPath(), '/api/')) {
            return $this->responses->json(['message' => 'Unauthenticated.'], 401);
        }

        return $this->responses->redirect('/login');
    }
}
