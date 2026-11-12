<?php

declare(strict_types=1);

namespace Nitro\Config;

use ArrayAccess;
use Nitro\Support\Arr;

/**
 * Dot-notation configuration repository.
 *
 * @implements ArrayAccess<string, mixed>
 */
final class Repository implements ArrayAccess
{
    /** @var array<string, mixed> */
    private array $items = [];

    /** @param array<string, mixed> $items */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function set(string $key, mixed $value): void
    {
        Arr::set($this->items, $key, $value);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->items, $key, $default);
    }

    public function has(string $key): bool
    {
        return Arr::has($this->items, $key);
    }

    public function all(): array
    {
        return $this->items;
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string) $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get((string) $offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set((string) $offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        Arr::forget($this->items, (string) $offset);
    }
}
