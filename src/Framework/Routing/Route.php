<?php

declare(strict_types=1);

namespace Nitro\Routing;

use Nitro\Container\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * A single registered route: method, pattern, handler and middleware stack.
 */
final class Route
{
    /** @var array<int, MiddlewareInterface|string> */
    private array $middleware = [];

    /** @var string|null */
    private ?string $name = null;

    /** @var array<int, string> Names of {placeholders} in order. */
    private array $parameterNames = [];

    private ?string $compiledRegex = null;

    public function __construct(
        private readonly string $method,
        private readonly string $pattern,
        private readonly string|callable $handler
    ) {
        $this->compile();
    }

    public function method(): string
    {
        return $this->method;
    }

    public function pattern(): string
    {
        return $this->pattern;
    }

    public function handler(): string|callable
    {
        return $this->handler;
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Attach middleware: class strings are resolved lazily from the container.
     *
     * @param MiddlewareInterface|string|array<int, MiddlewareInterface|string> $middleware
     */
    public function middleware(MiddlewareInterface|string|array $middleware): self
    {
        foreach ((array) $middleware as $item) {
            $this->middleware[] = $item;
        }

        return $this;
    }

    /** @return array<int, MiddlewareInterface|string> */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Assert the route matches the request path; returns URI parameters.
     *
     * @return array<string, string>|null
     */
    public function match(string $path): ?array
    {
        if (preg_match($this->toRegex(), $path, $matches) !== 1) {
            return null;
        }

        $parameters = [];

        foreach ($this->parameterNames as $name) {
            $parameters[$name] = urldecode($matches[$name] ?? '');
        }

        return $parameters;
    }

    private function toRegex(): string
    {
        return $this->compiledRegex ??= '#^' . $this->buildRegexBody() . '$#u';
    }

    private function buildRegexBody(): string
    {
        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}/',
            function (array $m): string {
                $name = $m[1];
                $constraint = $m[2] ?? '[^/]+';
                $this->parameterNames[] = $name;

                return sprintf('(?<%s>%s)', $name, $constraint);
            },
            $this->pattern
        ) ?? $this->pattern;

        return $pattern;
    }

    private function compile(): void
    {
        $this->compiledRegex = '#^' . $this->buildRegexBody() . '$#u';
    }

    /**
     * Dispatch the route handler; the controller action receives the request
     * followed by the matched URI parameters.
     *
     * @param array<string, string> $parameters
     */
    public function run(ServerRequestInterface $request, array $parameters, Container $container): ResponseInterface
    {
        $handler = $this->handler;

        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $method] = explode('@', $handler, 2);
            $controller = $container->make($class);
            $handler = [$controller, $method];
        } elseif (is_string($handler)) {
            $handler = $container->make($handler);
        }

        $arguments = array_values($parameters);

        $result = $handler($request, ...$arguments);

        if ($result instanceof ResponseInterface) {
            return $result;
        }

        throw new \LogicException(sprintf(
            'Route handler for "%s" must return a PSR-7 response, got %s.',
            $this->pattern,
            get_debug_type($result)
        ));
    }
}
