<?php

declare(strict_types=1);

namespace Nitro\Routing;

use Nitro\Container\Container;
use Nitro\Exceptions\MethodNotAllowedException;
use Nitro\Exceptions\NotFoundException;
use Nitro\Http\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * Route collection and dispatcher with static and dynamic matching.
 */
final class Router
{
    /** @var array<string, array<int, Route>> method => routes */
    private array $routes = [];

    /** @var array<int, MiddlewareInterface|string> */
    private array $globalMiddleware = [];

    private string $lastGroupPrefix = '';

    /** @var array<int, MiddlewareInterface|string> */
    private array $lastGroupMiddleware = [];

    public function __construct(private readonly Container $container)
    {
    }

    public function get(string $pattern, string|callable $handler): Route
    {
        return $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, string|callable $handler): Route
    {
        return $this->add('POST', $pattern, $handler);
    }

    public function put(string $pattern, string|callable $handler): Route
    {
        return $this->add('PUT', $pattern, $handler);
    }

    public function patch(string $pattern, string|callable $handler): Route
    {
        return $this->add('PATCH', $pattern, $handler);
    }

    public function delete(string $pattern, string|callable $handler): Route
    {
        return $this->add('DELETE', $pattern, $handler);
    }

    public function options(string $pattern, string|callable $handler): Route
    {
        return $this->add('OPTIONS', $pattern, $handler);
    }

    /**
     * Register a resource-style set of RESTful routes for a controller.
     */
    public function resource(string $pattern, string $controller): void
    {
        $this->get($pattern, $controller . '@index');
        $this->get($pattern . '/create', $controller . '@create');
        $this->post($pattern, $controller . '@store');
        $this->get($pattern . '/{id}', $controller . '@show');
        $this->get($pattern . '/{id}/edit', $controller . '@edit');
        $this->put($pattern . '/{id}', $controller . '@update');
        $this->patch($pattern . '/{id}', $controller . '@update');
        $this->delete($pattern . '/{id}', $controller . '@destroy');
    }

    /**
     * Group routes under a common prefix and middleware.
     *
     * @param array{prefix?: string, middleware?: array<int, MiddlewareInterface|string>} $attributes
     * @param callable(Router): void $callback
     */
    public function group(array $attributes, callable $callback): void
    {
        $previousPrefix = $this->lastGroupPrefix;
        $previousMiddleware = $this->lastGroupMiddleware;

        $this->lastGroupPrefix = $previousPrefix . ($attributes['prefix'] ?? '');
        $this->lastGroupMiddleware = array_merge(
            $previousMiddleware,
            $attributes['middleware'] ?? []
        );

        $callback($this);

        $this->lastGroupPrefix = $previousPrefix;
        $this->lastGroupMiddleware = $previousMiddleware;
    }

    public function add(string $method, string $pattern, string|callable $handler): Route
    {
        $method = strtoupper($method);
        $pattern = '/' . trim($this->lastGroupPrefix . '/' . ltrim($pattern, '/'), '/');

        $route = new Route($method, $pattern, $handler);
        $route->middleware($this->lastGroupMiddleware);

        $this->routes[$method][] = $route;

        return $route;
    }

    /** @param MiddlewareInterface|string|array<int, MiddlewareInterface|string> $middleware */
    public function use(MiddlewareInterface|string|array $middleware): void
    {
        foreach ((array) $middleware as $item) {
            $this->globalMiddleware[] = $item;
        }
    }

    /** @return array<int, MiddlewareInterface|string> */
    public function getGlobalMiddleware(): array
    {
        return $this->globalMiddleware;
    }

    /**
     * Match a request to a route.
     *
     * @return array{route: Route, parameters: array<string, string>}
     *
     * @throws NotFoundException|MethodNotAllowedException
     */
    public function match(ServerRequestInterface $request): array
    {
        $path = $this->normalizePath($request->getUri()->getPath());
        $method = Request::effectiveMethod($request);
        $allowed = [];

        foreach ($this->routes as $routeMethod => $routes) {
            foreach ($routes as $route) {
                $parameters = $route->match($path);

                if ($parameters === null) {
                    continue;
                }

                if ($routeMethod === $method) {
                    return ['route' => $route, 'parameters' => $parameters];
                }

                $allowed[] = $routeMethod;
            }
        }

        if ($allowed !== []) {
            throw new MethodNotAllowedException(array_values(array_unique($allowed)));
        }

        throw new NotFoundException(sprintf('No route matched "%s".', $path));
    }

    public function hasRoutes(): bool
    {
        return $this->routes !== [];
    }

    /** @return array<string, array<int, Route>> */
    public function all(): array
    {
        return $this->routes;
    }

    public function url(string $name, array $parameters = []): string
    {
        foreach ($this->routes as $routes) {
            foreach ($routes as $route) {
                if ($route->getName() !== $name) {
                    continue;
                }

                $url = $route->pattern();

                foreach ($parameters as $key => $value) {
                    $url = str_replace('{' . $key . '}', rawurlencode((string) $value), $url);
                }

                return $url;
            }
        }

        throw new \InvalidArgumentException(sprintf('Route "%s" is not named.', $name));
    }

    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
