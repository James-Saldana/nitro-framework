<?php

declare(strict_types=1);

namespace Nitro\Events;

use Closure;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * PSR-14 listener provider with wildcard support ("user.*" matches
 * "user.created"). Listeners are registered as callables resolved
 * through the container when they are class names.
 */
final class ListenerProvider implements ListenerProviderInterface
{
    /** @var array<string, array<int, callable>> */
    private array $listeners = [];

    /**
     * @param Closure|callable|string $listener Closure, callable or container-resolvable class name.
     */
    public function listen(string $event, Closure|callable|string $listener): void
    {
        $this->listeners[$event][] = $listener;
    }

    public function subscribe(string $subscriber): void
    {
        if (! class_exists($subscriber) || ! method_exists($subscriber, 'subscribe')) {
            throw new \InvalidArgumentException(sprintf('"%s" is not a valid event subscriber.', $subscriber));
        }

        $subscriber::subscribe($this);
    }

    /**
     * @param object $event
     * @return iterable<callable>
     */
    public function getListenersForEvent(object $event): iterable
    {
        $class = $event::class;
        $parts = explode('\\', $class);
        $shortName = end($parts);

        $patterns = array_unique([$class, '*', $shortName . '.*', '*.' . $shortName]);

        foreach ($patterns as $pattern) {
            foreach ($this->listeners[$pattern] ?? [] as $listener) {
                yield $this->normalize($listener);
            }
        }
    }

    /** @return array<string, array<int, callable>> */
    public function all(): array
    {
        return $this->listeners;
    }

    private function normalize(Closure|callable|string $listener): callable
    {
        if ($listener instanceof Closure || is_callable($listener)) {
            return $listener;
        }

        return static function (object $event) use ($listener): void {
            (new $listener())->handle($event);
        };
    }
}
