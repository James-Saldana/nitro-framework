<?php

declare(strict_types=1);

namespace Tests\Container;

use Nitro\Container\BindingResolutionException;
use Nitro\Container\Container;
use Nitro\Container\NotFoundException;
use Tests\TestCase;

final class ContainerTest extends TestCase
{
    public function testResolvesConcreteClassWithAutowiring(): void
    {
        $container = new Container();
        $service = $container->make(ConcreteService::class);

        self::assertInstanceOf(ConcreteService::class, $service);
        self::assertInstanceOf(DependedService::class, $service->dependency);
    }

    public function testSingletonSharesInstance(): void
    {
        $container = new Container();
        $container->singleton(DependedService::class);

        self::assertSame($container->make(DependedService::class), $container->make(DependedService::class));
    }

    public function testClosureBindingReceivesContainer(): void
    {
        $container = new Container();
        $container->bind('greeting', static fn (Container $c): string => 'hello');

        self::assertSame('hello', $container->make('greeting'));
    }

    public function testInstanceBinding(): void
    {
        $container = new Container();
        $instance = new DependedService();
        $container->instance(DependedService::class, $instance);

        self::assertSame($instance, $container->get(DependedService::class));
    }

    public function testAliasResolution(): void
    {
        $container = new Container();
        $container->singleton(DependedService::class);
        $container->alias(DependedService::class, 'dep');

        self::assertSame($container->make('dep'), $container->make(DependedService::class));
    }

    public function testTaggedResolution(): void
    {
        $container = new Container();
        $container->bind('a', ConcreteService::class);
        $container->bind('b', ConcreteService::class);
        $container->tag(['a', 'b'], 'handlers');

        self::assertCount(2, $container->tagged('handlers'));
    }

    public function testContextualBinding(): void
    {
        $container = new Container();
        $container->when(ConcreteService::class)
            ->needs(DependedService::class)
            ->give(new DependedService());

        $service = $container->make(ConcreteService::class);

        self::assertInstanceOf(DependedService::class, $service->dependency);
    }

    public function testThrowsNotFoundExceptionForUnknownId(): void
    {
        $this->expectException(NotFoundException::class);

        (new Container())->get('definitely.not.here');
    }

    public function testThrowsBindingResolutionForMissingClass(): void
    {
        $this->expectException(BindingResolutionException::class);

        (new Container())->make('NoSuchClass');
    }

    public function testCallResolvesTypedParameters(): void
    {
        $container = new Container();

        $result = $container->call(static fn (DependedService $dep): string => $dep::class);

        self::assertSame(DependedService::class, $result);
    }
}

final class DependedService
{
}

final class ConcreteService
{
    public function __construct(public readonly DependedService $dependency)
    {
    }
}
