<?php

declare(strict_types=1);

namespace Nitro\Events;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

/**
 * PSR-14 compliant event dispatcher.
 */
final class Dispatcher implements EventDispatcherInterface
{
    public function __construct(private readonly ListenerProviderInterface $provider)
    {
    }

    public function dispatch(object $event): object
    {
        foreach ($this->provider->getListenersForEvent($event) as $listener) {
            $listener($event);

            if ($event instanceof StoppableEventInterface && $event->isPropagationStopped()) {
                break;
            }
        }

        return $event;
    }
}
