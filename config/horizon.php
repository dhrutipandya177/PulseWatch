<?php

declare(strict_types=1);

return [
    'horizon_prefix' => env('HORIZON_PREFIX', 'pulsewatch-horizon:'),

    'domain' => env('HORIZON_DOMAIN', null),

    'path' => 'horizon',

    'use' => 'default',

    'prefix' => env('HORIZON_PREFIX', 'horizon:'),

    'middleware' => ['web'],

    'waits' => [
        'redis' => ['default' => 60],
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'metrics' => [
        'trim_snapshots' => [
            'command' => 24,
            'queue' => 168,
        ],
    ],

    'fast_termination' => false,

    'memory_limit' => 64,

    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default', 'monitor-checks', 'notifications'],
            'balance' => 'auto',
            'maxProcesses' => 10,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 120,
            'nice' => 0,
        ],
    ],

    'environments' => [],
];
