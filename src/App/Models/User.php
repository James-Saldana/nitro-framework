<?php

declare(strict_types=1);

namespace App\Models;

use Nitro\Container\Container;
use Nitro\Database\DatabaseManager;
use Nitro\Database\QueryBuilder;

/**
 * Thin active-record style model exposing the query builder.
 */
final class User
{
    public function __construct(
        public readonly int $id = 0,
        public readonly string $email = '',
        public readonly string $password = '',
        public readonly ?string $createdAt = null
    ) {
    }

    public static function query(): QueryBuilder
    {
        return Container::getInstance()
            ->make(DatabaseManager::class)
            ->table('users');
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'created_at' => $this->createdAt,
        ];
    }
}
