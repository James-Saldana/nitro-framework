<?php

declare(strict_types=1);

namespace Nitro\Middleware;

use Nitro\Container\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * PSR-15 middleware pipeline: wraps handlers in reverse registration order
 * so middleware executes in the order it was added.
 */
final class Pipeline implements RequestHandlerInterface
{
    /** @var array<int, MiddlewareInterface|string> */
    private array $middleware = [];

    /** @var callable(ServerRequestInterface): ResponseInterface|null */
    private $destination = null;

    public function __construct(private readonly Container $container)
    {
    }

    /**
     * @param MiddlewareInterface|string|array<int, MiddlewareInterface|string> $middleware
     */
    public function through(MiddlewareInterface|string|array $middleware): self
    {
        foreach ((array) $middleware as $item) {
            $this->middleware[] = $item;
        }

        return $this;
    }

    /**
     * @param callable(ServerRequestInterface): ResponseInterface $destination
     */
    public function then(callable $destination): self
    {
        $this->destination = $destination;

        return $this;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->destination === null) {
            throw new \LogicException('Pipeline destination has not been set; call then() first.');
        }

        return $this->buildHandler($this->middleware, $this->destination)->handle($request);
    }

    public function run(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handle($request);
    }

    /**
     * @param array<int, MiddlewareInterface|string> $middleware
     * @param callable(ServerRequestInterface): ResponseInterface $destination
     */
    private function buildHandler(array $middleware, callable $destination): RequestHandlerInterface
    {
        if ($middleware === []) {
            return new CallableHandler($destination);
        }

        $first = array_shift($middleware);

        return new LayeredHandler($this->resolve($first), $this->buildHandler($middleware, $destination));
    }

    private function resolve(MiddlewareInterface|string $middleware): MiddlewareInterface
    {
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        $resolved = $this->container->make($middleware);

        if (! $resolved instanceof MiddlewareInterface) {
            throw new \LogicException(sprintf(
                '"%s" does not implement Psr\Http\Server\MiddlewareInterface.',
                $middleware
            ));
        }

        return $resolved;
    }
}

/**
 * @internal Terminal handler invoking the pipeline destination.
 */
final class CallableHandler implements RequestHandlerInterface
{
    /** @var callable(ServerRequestInterface): ResponseInterface */
    private $destination;

    public function __construct(callable $destination)
    {
        $this->destination = $destination;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return ($this->destination)($request);
    }
}

/**
 * @internal Applies one middleware and delegates to the next handler.
 */
final class LayeredHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly MiddlewareInterface $middleware,
        private readonly RequestHandlerInterface $next
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->middleware->process($request, $this->next);
    }
}
