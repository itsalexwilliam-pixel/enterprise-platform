<?php

namespace App\Services\Validation;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * ============================================================
 * Spam Trap & Honeypot Detector
 * Protects users from sending to known spam traps
 * ============================================================
 */
class SpamTrapDetector
{
    private const CACHE_TTL = 3600;

    // Known spam trap domains (partial list — full list in DB)
    private const KNOWN_SPAM_TRAP_DOMAINS = [
        'spamtrap.abuse.ch', 'spamcop.net', 'spamhaus.org',
        'ucepprotect.net', 'barracudacentral.org', 'spamrats.com',
        'mailspike.net', 'senderscore.net',
    ];

    // Known toxic/abusive domains
    private const TOXIC_DOMAINS = [
        'trashmail.com', 'spam4.me', 'mailinator.com',
        'guerrillamail.com', 'throwam.com', 'maildrop.cc',
    ];

    // Known honeypot prefixes
    private const HONEYPOT_PATTERNS = [
        '/^noreply@/i',
        '/^no-reply@/i',
        '/^donotreply@/i',
        '/^do-not-reply@/i',
        '/^abuse@abuse\./i',
        '/^postmaster@postmaster\./i',
    ];

    /**
     * Check if email/domain is a spam trap or toxic
     */
    public function check(string $email, string $domain): array
    {
        $result = [
            'is_spam_trap' => false,
            'is_honeypot'  => false,
            'is_toxic'     => false,
        ];

        $domain = strtolower($domain);
        $email  = strtolower($email);

        // Check against built-in spam trap domains
        if (in_array($domain, self::KNOWN_SPAM_TRAP_DOMAINS, true)) {
            $result['is_spam_trap'] = true;
            return $result;
        }

        // Check against toxic domains
        if (in_array($domain, self::TOXIC_DOMAINS, true)) {
            $result['is_toxic'] = true;
            return $result;
        }

        // Check honeypot patterns
        foreach (self::HONEYPOT_PATTERNS as $pattern) {
            if (preg_match($pattern, $email)) {
                $result['is_honeypot'] = true;
                return $result;
            }
        }

        // Database check
        $dbResult = Cache::remember("spam_trap:{$domain}", self::CACHE_TTL, function () use ($domain) {
            return DB::table('spam_trap_domains')
                ->where('domain', $domain)
                ->where('is_active', true)
                ->first(['type']);
        });

        if ($dbResult) {
            $result['is_spam_trap'] = $dbResult->type === 'spam_trap';
            $result['is_honeypot']  = $dbResult->type === 'honeypot';
            $result['is_toxic']     = $dbResult->type === 'toxic';
        }

        return $result;
    }

    /**
     * Add a spam trap domain to the database
     */
    public function addSpamTrap(string $domain, string $type = 'spam_trap', string $source = ''): void
    {
        DB::table('spam_trap_domains')->updateOrInsert(
            ['domain' => strtolower($domain)],
            [
                'type'       => $type,
                'source'     => $source,
                'is_active'  => true,
                'updated_at' => now(),
            ]
        );

        Cache::forget("spam_trap:{$domain}");
    }
}
