<?php

declare(strict_types=1);

namespace Nitro;

use Nitro\Container\Container;
use Nitro\Exceptions\Handler;
use Nitro\Http\Emitter;
use Nitro\Http\ResponseFactory;
use Nitro\Middleware\Pipeline;
use Nitro\Providers\AuthServiceProvider;
use Nitro\Providers\ConfigServiceProvider;
use Nitro\Providers\DatabaseServiceProvider;
use Nitro\Providers\EventServiceProvider;
use Nitro\Providers\ServiceProvider;
use Nitro\Providers\ViewServiceProvider;
use Nitro\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * The application kernel: bootstraps the container, registers service
 * providers and serves PSR-15 requests. Implements RequestHandlerInterface.
 */
final class Application implements RequestHandlerInterface
{
    public const VERSION = '1.0.0';

    private Container $container;

    /** @var array<int, class-string<ServiceProvider>> */
    private array $providers = [];

    /** @var array<int, ServiceProvider> */
    private array $registered = [];

    private bool $booted = false;

    public function __construct(string $basePath)
    {
        $this->container = new Container();
        Container::setInstance($this->container);

        $this->container->instance(Application::class, $this);
        $this->container->instance('app', $this);

        $this->container->instance('path.base', rtrim($basePath, '/'));
        $this->container->instance('path.config', $this->path('config'));
        $this->container->instance('path.routes', $this->path('routes'));
        $this->container->instance('path.resources', $this->path('resources'));
        $this->container->instance('path.storage', $this->path('storage'));
        $this->container->instance('path.env', $this->path('.env'));
    }

    public function path(string $relative = ''): string
    {
        return rtrim($this->container->make('path.base'), '/') . ($relative !== '' ? '/' . $relative : '');
    }

    /**
     * Register additional service providers (framework or application).
     *
     * @param class-string<ServiceProvider>|array<int, class-string<ServiceProvider>> $providers
     */
    public function register(string|array $providers): self
    {
        foreach ((array) $providers as $provider) {
            $this->providers[] = $provider;
        }

        return $this;
    }

    /**
     * Register global middleware applied to every request.
     *
     * @param array<int, mixed> $middleware
     */
    public function middleware(array $middleware): self
    {
        $this->container->make(Router::class)->use($middleware);

        return $this;
    }

    public function container(): Container
    {
        return $this->container;
    }

    public function version(): string
    {
        return self::VERSION;
    }

    /**
     * Boot all registered providers: register() then boot().
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->register([
            ConfigServiceProvider::class,
            EventServiceProvider::class,
            DatabaseServiceProvider::class,
            AuthServiceProvider::class,
            ViewServiceProvider::class,
        ]);

        foreach ($this->providers as $class) {
            $provider = $this->container->make($class);
            $provider->register();
            $this->registered[] = $provider;
        }

        foreach ($this->registered as $provider) {
            $provider->boot();
        }

        $this->booted = true;
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * PSR-15 handler: dispatch the request through the middleware pipeline
     * and the router.
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->boot();

        $router = $this->container->make(Router::class);
        $handler = $this->container->make(Handler::class);

        try {
            $pipeline = new Pipeline($this->container);
            $pipeline->through($router->getGlobalMiddleware());
            $pipeline->then(function (ServerRequestInterface $request) use ($router): ResponseInterface {
                $match = $router->match($request);
                $route = $match['route'];

                $routePipeline = new Pipeline($this->container);
                $routePipeline->through($route->getMiddleware());
                $routePipeline->then(
                    fn (ServerRequestInterface $request): ResponseInterface => $route->run(
                        $request,
                        $match['parameters'],
                        $this->container
                    )
                );

                return $routePipeline->handle($request);
            });

            return $pipeline->handle($request);
        } catch (Throwable $e) {
            return $handler->render($request, $e);
        }
    }

    /**
     * Create a request from PHP superglobals, handle it and emit the response.
     */
    public function run(): void
    {
        $request = \GuzzleHttp\Psr7\ServerRequest::fromGlobals();

        $response = $this->handle($request);

        (new Emitter())->emit($response);
    }

    public function response(): ResponseFactory
    {
        return $this->container->make(ResponseFactory::class);
    }
}
