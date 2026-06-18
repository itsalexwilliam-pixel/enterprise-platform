<?php

return [
    'default' => env('QUEUE_CONNECTION', 'database'),

    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],

        'redis' => [
            'driver'       => 'redis',
            'connection'   => 'default',
            'queue'        => env('REDIS_QUEUE', 'default'),
            'retry_after'  => 90,
            'block_for'    => null,
            'after_commit' => false,
        ],

        'rabbitmq' => [
            'driver'  => 'rabbitmq',
            'queue'   => 'default',
            'connection' => PhpAmqpLib\Connection\AMQPLazyConnection::class,
            'hosts'   => [
                [
                    'host'      => env('RABBITMQ_HOST', '127.0.0.1'),
                    'port'      => env('RABBITMQ_PORT', 5672),
                    'user'      => env('RABBITMQ_USER', 'guest'),
                    'password'  => env('RABBITMQ_PASSWORD', 'guest'),
                    'vhost'     => env('RABBITMQ_VHOST', '/'),
                ],
            ],
            'options' => [
                'ssl_options' => [
                    'cafile'      => env('RABBITMQ_SSL_CAFILE', null),
                    'local_cert'  => env('RABBITMQ_SSL_LOCALCERT', null),
                    'local_key'   => env('RABBITMQ_SSL_LOCALKEY', null),
                    'verify_peer' => env('RABBITMQ_SSL_VERIFY_PEER', true),
                    'passphrase'  => env('RABBITMQ_SSL_PASSPHRASE', null),
                ],
                'queue' => [
                    'job' => VladimirYuldashev\LaravelQueueRabbitMQ\Queue\Jobs\RabbitMQJob::class,
                ],
            ],
        ],

        'database' => [
            'driver'       => 'database',
            'connection'   => null,
            'table'        => 'jobs',
            'queue'        => 'default',
            'retry_after'  => 90,
            'after_commit' => false,
        ],
    ],

    'batching' => [
        'database'   => env('DB_CONNECTION', 'mysql'),
        'table'      => 'job_batches',
    ],

    'failed' => [
        'driver'    => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database'  => env('DB_CONNECTION', 'mysql'),
        'table'     => 'failed_jobs',
    ],

    // Named queues configuration
    'queues' => [
        'smtp'     => env('QUEUE_SMTP', 'smtp_validation'),
        'dns'      => env('QUEUE_DNS', 'dns_validation'),
        'bulk'     => env('QUEUE_BULK', 'bulk_processing'),
        'webhooks' => env('QUEUE_WEBHOOKS', 'webhooks'),
        'reports'  => env('QUEUE_REPORTS', 'reports'),
    ],
];
