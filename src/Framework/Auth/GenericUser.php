<?php

declare(strict_types=1);

namespace Nitro\Auth;

/**
 * Default user implementation hydrated from a database row.
 */
final class GenericUser implements Authenticatable
{
    /** @param array<string, mixed> $row */
    public function __construct(
        private readonly string|int $id,
        private readonly string $password,
        private readonly array $attributes
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (int) ($row['id'] ?? 0),
            (string) ($row['password'] ?? ''),
            $row
        );
    }

    public function getAuthIdentifier(): string|int
    {
        return $this->id;
    }

    public function getAuthPassword(): string
    {
        return $this->password;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $attributes = $this->attributes;
        unset($attributes['password']);

        return $attributes;
    }
}
