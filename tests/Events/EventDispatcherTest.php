<?php

declare(strict_types=1);

namespace Tests\Events;

use Nitro\Events\Dispatcher;
use Nitro\Events\ListenerProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EventDispatcherTest extends TestCase
{
    public function testDispatchesToMatchingListeners(): void
    {
        $provider = new ListenerProvider();
        $provider->listen(DomainEvent::class, static function (DomainEvent $e): void {
            $e->log[] = 'first';
        });
        $provider->listen(DomainEvent::class, static function (DomainEvent $e): void {
            $e->log[] = 'second';
        });

        $event = new DomainEvent();
        (new Dispatcher($provider))->dispatch($event);

        self::assertSame(['first', 'second'], $event->log);
    }

    public function testPropagationStopsRemainingListeners(): void
    {
        $provider = new ListenerProvider();
        $provider->listen(DomainEvent::class, static function (DomainEvent $e): void {
            $e->log[] = 'stopper';
            $e->stopPropagation();
        });
        $provider->listen(DomainEvent::class, static function (DomainEvent $e): void {
            $e->log[] = 'never';
        });

        $event = new DomainEvent();
        (new Dispatcher($provider))->dispatch($event);

        self::assertSame(['stopper'], $event->log);
    }

    public function testWildcardListenersMatch(): void
    {
        $provider = new ListenerProvider();
        $provider->listen('DomainEvent.*', static function (DomainEvent $e): void {
            $e->log[] = 'wildcard';
        });

        $event = new DomainEvent();
        (new Dispatcher($provider))->dispatch($event);

        self::assertSame(['wildcard'], $event->log);
    }

    public function testNonMatchingEventsAreNotDelivered(): void
    {
        $provider = new ListenerProvider();
        $provider->listen(\stdClass::class, static function (): void {
            throw new RuntimeException('Should not be called.');
        });

        $event = new DomainEvent();
        (new Dispatcher($provider))->dispatch($event);

        self::assertSame([], $event->log);
    }
}

final class DomainEvent
{
    /** @var array<int, string> */
    public array $log = [];
}
