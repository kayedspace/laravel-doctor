<?php

return [
    'default' => 'risky_connection',
    'connections' => [
        'risky_connection' => [
            'driver' => 'redis',
            'timeout' => 60,
            'retry_after' => 60,
            'after_commit' => false,
        ],
        'safe_connection' => [
            'driver' => 'redis',
            'timeout' => 30,
            'retry_after' => 60,
            'after_commit' => true,
        ],
        'sync_connection' => [
            'driver' => 'sync',
            'after_commit' => false,
        ],
    ],
];
