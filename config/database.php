<?php

declare(strict_types=1);

return [
    'default' => 'default',

    'connections' => [
        'default' => [
            'driver' => 'sqlite',
            'database' => __DIR__ . '/../storage/database.sqlite',
        ],
    ],
];
