<?php

/**
 * ============================================================
 * Email Validation Configuration
 * Enterprise Email Validation Platform
 * ============================================================
 */

return [

    // ============================================================
    // SMTP VALIDATION SETTINGS
    // ============================================================
    'smtp' => [
        'helo_domain'       => env('SMTP_HELO_DOMAIN', 'mail.validator.com'),
        'from_email'        => env('SMTP_FROM_EMAIL', 'verify@validator.com'),
        'timeout'           => (int) env('SMTP_TIMEOUT', 10),
        'connect_timeout'   => (int) env('SMTP_CONNECT_TIMEOUT', 5),
        'max_retries'       => (int) env('SMTP_MAX_RETRIES', 2),
        'ports'             => [25, 587, 465],
        'enabled'           => env('SMTP_VALIDATION_ENABLED', true),
    ],

    // ============================================================
    // DNS VALIDATION SETTINGS
    // ============================================================
    'dns' => [
        'timeout'           => (int) env('DNS_TIMEOUT', 5),
        'nameservers'       => explode(',', env('DNS_NAMESERVERS', '8.8.8.8,1.1.1.1')),
        'cache_ttl'         => (int) env('DNS_CACHE_TTL', 3600),
    ],

    // ============================================================
    // CATCH-ALL DETECTION
    // ============================================================
    'catch_all' => [
        'enabled'           => true,
        'test_prefix'       => 'this-email-should-not-exist-xyz123abc',
        'cache_ttl'         => 3600,
    ],

    // ============================================================
    // BULK VALIDATION SETTINGS
    // ============================================================
    'bulk' => [
        'max_emails'        => (int) env('BULK_MAX_EMAILS', 10000000),
        'chunk_size'        => (int) env('BULK_CHUNK_SIZE', 500),
        'max_file_size_mb'  => 100,
        'allowed_types'     => ['csv', 'txt', 'xlsx'],
        'concurrent_jobs'   => (int) env('VALIDATION_CONCURRENT_JOBS', 50),
    ],

    // ============================================================
    // API RATE LIMITS
    // ============================================================
    'api' => [
        'rate_limit_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 100),
        'rate_limit_per_day'    => (int) env('API_RATE_LIMIT_PER_DAY', 10000),
        'batch_max_size'        => 100,
    ],

    // ============================================================
    // CACHING
    // ============================================================
    'cache' => [
        'valid_ttl'         => 86400,   // 24 hours
        'invalid_ttl'       => 43200,   // 12 hours
        'disposable_ttl'    => 604800,  // 7 days
        'spam_trap_ttl'     => 604800,  // 7 days
        'domain_ttl'        => 3600,    // 1 hour
        'mx_ttl'            => 3600,    // 1 hour
    ],

    // ============================================================
    // SCORING ENGINE
    // ============================================================
    'scoring' => [
        'thresholds' => [
            'excellent'     => 80,
            'good'          => 60,
            'fair'          => 40,
            'poor'          => 20,
        ],
        'risk_levels' => [
            'low'           => 80,
            'medium'        => 50,
            'high'          => 20,
        ],
    ],

    // ============================================================
    // QUEUES
    // ============================================================
    'queues' => [
        'smtp'              => env('QUEUE_SMTP', 'smtp_validation'),
        'dns'               => env('QUEUE_DNS', 'dns_validation'),
        'bulk'              => env('QUEUE_BULK', 'bulk_processing'),
        'webhooks'          => env('QUEUE_WEBHOOKS', 'webhooks'),
        'reports'           => env('QUEUE_REPORTS', 'reports'),
    ],

    // ============================================================
    // DOWNLOAD SETTINGS
    // ============================================================
    'downloads' => [
        'expiry_days'       => 7,
        'formats'           => ['csv', 'xlsx', 'json'],
    ],

    // ============================================================
    // KNOWN MAILBOX PROVIDERS
    // ============================================================
    'providers' => [
        'gmail'             => ['gmail.com', 'googlemail.com'],
        'outlook'           => ['outlook.com', 'hotmail.com', 'live.com', 'msn.com'],
        'yahoo'             => ['yahoo.com', 'ymail.com'],
        'icloud'            => ['icloud.com', 'me.com', 'mac.com'],
        'protonmail'        => ['protonmail.com', 'pm.me', 'proton.me'],
    ],
];
