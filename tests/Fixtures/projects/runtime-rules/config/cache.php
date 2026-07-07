<?php

return [
    'default' => 'risky_store',
    'stores' => [
        'risky_store' => [
            'driver' => 'file',
            'prefix' => 'laravel',
        ],
        'safe_store' => [
            'driver' => 'redis',
            'prefix' => 'my_unique_app_prefix_cache',
        ],
        'empty_prefix_store' => [
            'driver' => 'redis',
            'prefix' => '',
        ],
    ],
];
