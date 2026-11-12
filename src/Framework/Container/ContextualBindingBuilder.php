<?php

declare(strict_types=1);

namespace Nitro\Container;

use Closure;

/**
 * Fluent builder for contextual ("when/needs/give") bindings.
 */
final class ContextualBindingBuilder
{
    private ?string $needs = null;

    public function __construct(
        private readonly Container $container,
        private readonly string $concrete
    ) {
    }

    public function needs(string $abstract): self
    {
        $this->needs = $abstract;

        return $this;
    }

    public function give(Closure|string $implementation): void
    {
        $this->container->addContextualBinding(
            $this->concrete,
            $this->needs ?? throw new \LogicException('Call needs() before give().'),
            $implementation
        );
    }
}
