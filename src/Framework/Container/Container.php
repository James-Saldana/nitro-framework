<?php

declare(strict_types=1);

namespace Nitro\Container;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * PSR-11 compliant IoC container with constructor autowiring,
 * contextual bindings, singletons and tagged resolution.
 */
final class Container implements ContainerInterface
{
    /** @var array<string, Closure|string|array|object> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $instances = [];

    /** @var array<string, bool> */
    private array $shared = [];

    /** @var array<string, string> */
    private array $aliases = [];

    /** @var array<string, array<int, string>> */
    private array $tags = [];

    /** @var array<int, array{concrete: string, needs: string, implementation: Closure|string}> */
    private array $contextual = [];

    private static ?Container $globalInstance = null;

    public function __construct()
    {
        self::$globalInstance ??= $this;
    }

    /**
     * Register a binding into the container.
     *
     * @param Closure|string|array|object $concrete Closure factory, class name or pre-built value.
     */
    public function bind(string $abstract, Closure|string|array|object $concrete = null, bool $shared = false): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->dropStaleInstances($abstract);

        $this->bindings[$abstract] = $concrete;
        $this->shared[$abstract] = $shared;
    }

    /**
     * Register a shared (singleton) binding.
     */
    public function singleton(string $abstract, Closure|string|array|object $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register an existing instance as a shared binding.
     */
    public function instance(string $abstract, object $instance): object
    {
        $this->dropStaleInstances($abstract);
        $this->instances[$abstract] = $instance;

        return $instance;
    }

    /**
     * Register an alias so that $alias resolves to $abstract.
     */
    public function alias(string $abstract, string $alias): void
    {
        $this->aliases[$alias] = $abstract;
    }

    /**
     * Tag a set of implementations for later resolution via tagged().
     */
    public function tag(string|array $abstracts, string $tag): void
    {
        foreach ((array) $abstracts as $abstract) {
            $this->tags[$tag][] = $abstract;
        }
    }

    /**
     * Resolve every implementation registered under a tag.
     *
     * @return array<int, mixed>
     */
    public function tagged(string $tag): array
    {
        $resolved = [];

        foreach ($this->tags[$tag] ?? [] as $abstract) {
            $resolved[] = $this->make($abstract);
        }

        return $resolved;
    }

    /**
     * Define a contextual binding: when building $concrete and $concrete
     * needs $abstract, inject $implementation instead.
     */
    public function when(string $concrete): ContextualBindingBuilder
    {
        return new ContextualBindingBuilder($this, $concrete);
    }

    /** @internal Used by ContextualBindingBuilder. */
    public function addContextualBinding(string $concrete, string $abstract, Closure|string $implementation): void
    {
        $this->contextual[] = compact('concrete', 'abstract', 'implementation');
    }

    public function has(string $id): bool
    {
        return isset($this->bindings[$id])
            || isset($this->instances[$id])
            || class_exists($id);
    }

    /**
     * Resolve an entry; throws NotFoundExceptionInterface when unresolvable.
     */
    public function get(string $id): mixed
    {
        try {
            return $this->make($id);
        } catch (BindingResolutionException $e) {
            throw new NotFoundException($e->getMessage(), previous: $e);
        }
    }

    /**
     * Resolve an entry by class name or closure factory.
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        $abstract = $this->aliases[$abstract] ?? $abstract;

        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $concrete = $this->bindings[$abstract] ?? $abstract;

        if ($concrete instanceof Closure) {
            $object = $concrete($this, $parameters);
        } elseif (is_object($concrete)) {
            $object = $concrete;
        } elseif (is_array($concrete)) {
            $object = $concrete;
        } else {
            $object = $this->build($concrete, $parameters);
        }

        if ($this->shared[$abstract] ?? false) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Instantiate a class, recursively resolving constructor dependencies.
     */
    public function build(string $class, array $parameters = []): object
    {
        if (! class_exists($class)) {
            throw new BindingResolutionException(sprintf('Class "%s" does not exist.', $class));
        }

        $reflector = new ReflectionClass($class);

        if (! $reflector->isInstantiable()) {
            throw new BindingResolutionException(sprintf('Class "%s" is not instantiable.', $class));
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $arguments[] = $this->resolveParameter($class, $parameter, $parameters);
        }

        return $reflector->newInstanceArgs($arguments);
    }

    /**
     * Invoke a callable resolving its typed dependencies.
     */
    public function call(callable $callable, array $parameters = []): mixed
    {
        $closure = Closure::fromCallable($callable);
        $reflector = new \ReflectionFunction($closure);
        $arguments = [];

        foreach ($reflector->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $parameters)) {
                $arguments[] = $parameters[$name];
                continue;
            }

            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $arguments[] = $this->make($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new BindingResolutionException(
                sprintf('Unable to resolve parameter "$%s" for callable.', $name)
            );
        }

        return $reflector->invokeArgs($arguments);
    }

    public function forget(string $abstract): void
    {
        $abstract = $this->aliases[$abstract] ?? $abstract;

        unset($this->bindings[$abstract], $this->instances[$abstract], $this->shared[$abstract]);
    }

    public function flush(): void
    {
        $this->bindings = [];
        $this->instances = [];
        $this->shared = [];
        $this->aliases = [];
        $this->tags = [];
        $this->contextual = [];
    }

    public static function getInstance(): Container
    {
        return self::$globalInstance ??= new self();
    }

    public static function setInstance(Container $container): void
    {
        self::$globalInstance = $container;
    }

    private function dropStaleInstances(string $abstract): void
    {
        unset($this->instances[$abstract]);
    }

    private function resolveParameter(string $class, ReflectionParameter $parameter, array $parameters): mixed
    {
        $name = $parameter->getName();

        if (array_key_exists($name, $parameters)) {
            return $parameters[$name];
        }

        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            if ($parameter->allowsNull()) {
                return null;
            }

            throw new BindingResolutionException(sprintf(
                'Unable to resolve parameter "$%s" while building "%s".',
                $name,
                $class
            ));
        }

        $dependency = $type->getName();

        if ($implementation = $this->findContextualImplementation($class, $dependency)) {
            return $implementation instanceof Closure
                ? $implementation($this)
                : $this->make($implementation);
        }

        try {
            return $this->make($dependency);
        } catch (BindingResolutionException|NotFoundException $e) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            if ($parameter->allowsNull()) {
                return null;
            }

            throw $e;
        }
    }

    private function findContextualImplementation(string $class, string $dependency): Closure|string|null
    {
        foreach ($this->contextual as $binding) {
            if ($binding['concrete'] === $class && $binding['abstract'] === $dependency) {
                return $binding['implementation'];
            }
        }

        return null;
    }
}
