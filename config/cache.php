<?php

use Illuminate\Support\Str;

return [
    'default' => env('CACHE_DRIVER', 'redis'),

    'stores' => [
        'redis' => [
            'driver'     => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'default',
        ],
        'file' => [
            'driver' => 'file',
            'path'   => storage_path('framework/cache/data'),
        ],
        'array' => [
            'driver'    => 'array',
            'serialize' => false,
        ],
        'database' => [
            'driver' => 'database',
            'table'  => 'cache',
            'connection'  => null,
            'lock_connection' => null,
        ],
    ],

    'prefix' => env('CACHE_PREFIX', Str::slug(env('APP_NAME', 'email_validator'), '_').'_cache_'),
];
