<?php

declare(strict_types=1);

namespace Nitro\Events;

use Psr\EventDispatcher\StoppableEventInterface;

/**
 * Base event with propagation control.
 */
abstract class Event implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
