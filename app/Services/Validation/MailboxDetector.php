<?php

namespace App\Services\Validation;

/**
 * ============================================================
 * Mailbox Provider Detector
 * Identifies email provider from domain and MX records
 * ============================================================
 */
class MailboxDetector
{
    // Provider detection rules: [domain pattern or MX pattern => provider name]
    private const PROVIDERS = [
        // Google / Gmail
        'gmail' => [
            'domains' => ['gmail.com', 'googlemail.com'],
            'mx_patterns' => ['google.com', 'googlemail.com', 'smtp.google.com'],
            'type' => 'free',
        ],

        // Microsoft Outlook / Office 365 / Hotmail / Live
        'outlook' => [
            'domains' => ['outlook.com', 'hotmail.com', 'live.com', 'msn.com', 'passport.com'],
            'mx_patterns' => ['outlook.com', 'hotmail.com'],
            'type' => 'free',
        ],

        // Microsoft Office 365 (corporate)
        'office365' => [
            'domains' => [],
            'mx_patterns' => ['mail.protection.outlook.com', 'eo.outlook.com'],
            'type' => 'corporate',
        ],

        // Yahoo Mail
        'yahoo' => [
            'domains' => [
                'yahoo.com', 'yahoo.co.uk', 'yahoo.co.in', 'yahoo.fr',
                'yahoo.de', 'yahoo.es', 'yahoo.it', 'yahoo.ca',
                'yahoo.com.br', 'yahoo.com.au', 'ymail.com', 'rocketmail.com',
            ],
            'mx_patterns' => ['yahoo.com', 'yahoodns.net', 'ymail.com'],
            'type' => 'free',
        ],

        // Apple iCloud
        'icloud' => [
            'domains' => ['icloud.com', 'me.com', 'mac.com'],
            'mx_patterns' => ['icloud.com'],
            'type' => 'free',
        ],

        // Zoho
        'zoho' => [
            'domains' => ['zoho.com', 'zohomail.com'],
            'mx_patterns' => ['zoho.com'],
            'type' => 'paid',
        ],

        // ProtonMail
        'protonmail' => [
            'domains' => ['protonmail.com', 'protonmail.ch', 'pm.me', 'proton.me'],
            'mx_patterns' => ['protonmail.ch'],
            'type' => 'free',
        ],

        // Fastmail
        'fastmail' => [
            'domains' => ['fastmail.com', 'fastmail.fm'],
            'mx_patterns' => ['fastmail.com', 'messagingengine.com'],
            'type' => 'paid',
        ],

        // GMX
        'gmx' => [
            'domains' => ['gmx.com', 'gmx.net', 'gmx.de'],
            'mx_patterns' => ['gmx.net'],
            'type' => 'free',
        ],

        // AOL
        'aol' => [
            'domains' => ['aol.com', 'aim.com'],
            'mx_patterns' => ['aol.com', 'mx.aol.com'],
            'type' => 'free',
        ],

        // Yandex
        'yandex' => [
            'domains' => ['yandex.ru', 'yandex.com', 'ya.ru'],
            'mx_patterns' => ['yandex.net', 'yandex.ru'],
            'type' => 'free',
        ],

        // Mail.ru
        'mailru' => [
            'domains' => ['mail.ru', 'inbox.ru', 'bk.ru', 'list.ru'],
            'mx_patterns' => ['mail.ru'],
            'type' => 'free',
        ],

        // Tutanota (encrypted)
        'tutanota' => [
            'domains' => ['tutanota.com', 'tutanota.de', 'tuta.io', 'tutamail.com'],
            'mx_patterns' => ['tutanota.de'],
            'type' => 'free',
        ],

        // Google Workspace (corporate Gmail)
        'google_workspace' => [
            'domains' => [],
            'mx_patterns' => ['aspmx.l.google.com', 'googlemail.com', 'google.com'],
            'type' => 'corporate',
        ],
    ];

    /**
     * Detect mailbox provider from domain and MX host
     */
    public function detect(string $domain, string $mxHost = ''): array
    {
        $domain = strtolower($domain);
        $mxHost = strtolower($mxHost);

        foreach (self::PROVIDERS as $providerName => $config) {
            // Check exact domain match
            if (in_array($domain, $config['domains'], true)) {
                return [
                    'provider' => $providerName,
                    'type'     => $config['type'],
                ];
            }

            // Check MX record patterns
            if (! empty($mxHost)) {
                foreach ($config['mx_patterns'] as $pattern) {
                    if (str_contains($mxHost, $pattern)) {
                        return [
                            'provider' => $providerName,
                            'type'     => $config['type'],
                        ];
                    }
                }
            }
        }

        // Unknown provider - try to determine if corporate
        $type = $this->guessProviderType($domain, $mxHost);

        return [
            'provider' => 'other',
            'type'     => $type,
        ];
    }

    /**
     * Guess if provider is corporate or unknown
     */
    private function guessProviderType(string $domain, string $mxHost): string
    {
        // Office 365 MX pattern
        if (str_contains($mxHost, 'outlook.com')) {
            return 'corporate';
        }

        // Google Workspace
        if (str_contains($mxHost, 'google.com') || str_contains($mxHost, 'googlemail.com')) {
            return 'corporate';
        }

        // Self-hosted indicators
        if (str_contains($mxHost, 'mail.' . $domain) || str_contains($mxHost, 'mx.' . $domain)) {
            return 'corporate';
        }

        return 'unknown';
    }

    /**
     * Get all supported providers
     */
    public function getSupportedProviders(): array
    {
        return array_keys(self::PROVIDERS);
    }
}
