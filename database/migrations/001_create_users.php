<?php

declare(strict_types=1);

use Nitro\Database\Connection;
use Nitro\Support\PasswordHasher;

/**
 * Creates the users table and seeds a demo account.
 */
return static function (Connection $connection): void {
    $connection->statement('
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            created_at TEXT NOT NULL
        )
    ');

    $connection->insert('users', [
        'email' => 'admin@example.test',
        'password' => PasswordHasher::hash('secret-password'),
        'created_at' => date('Y-m-d H:i:s'),
    ]);
};
