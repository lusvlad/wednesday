<?php

return [
    'redis' => [
        'client' => 'phpredis',
        'default' => [
            'host' => env('REDIS_HOST', 'redis'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', 6379),
            'database' => 0,
        ],
    ],
];
