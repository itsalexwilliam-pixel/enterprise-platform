<?php

namespace App\Services\Validation;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * ============================================================
 * Disposable Email Detector
 * Detects temporary/fake email addresses
 * Database of 100,000+ disposable domains
 * ============================================================
 */
class DisposableDetector
{
    private const CACHE_KEY = 'disposable_domains_set';
    private const CACHE_TTL = 3600; // 1 hour

    // Built-in disposable domain patterns (supplemented by DB)
    private const BUILT_IN_DISPOSABLE = [
        'mailinator.com', 'guerrillamail.com', 'tempmail.com', 'throwam.com',
        'trashmail.com', 'fakeinbox.com', 'maildrop.cc', 'dispostable.com',
        'sharklasers.com', 'guerrillamailblock.com', 'grr.la', 'guerrillamail.info',
        'guerrillamail.biz', 'guerrillamail.de', 'guerrillamail.net',
        'guerrillamail.org', 'spam4.me', 'yopmail.com', 'yopmail.fr',
        'cool.fr.nf', 'jetable.fr.nf', 'nospam.ze.tc', 'nomail.xl.cx',
        'mega.zik.dj', 'speed.1s.fr', 'courriel.fr.nf', 'moncourrier.fr.nf',
        'monemail.fr.nf', 'monmail.fr.nf', 'discard.email', 'spamgourmet.com',
        'spamgourmet.net', 'spamgourmet.org', 'trashmail.at', 'trashmail.io',
        'trashmail.me', 'trashmail.net', 'tempinbox.com', 'throwam.com',
        'getairmail.com', 'filzmail.com', 'spamherelots.com', 'spamhere.eu',
        'jnxjn.com', 'trashmail.org', 'trashmailer.com', 'mt2014.com',
        'mt2015.com', 'comsafe-mail.net', 'e4ward.com', 'mierdamail.com',
        'spamtrap.ro', 'trash2009.com', 'trash2010.com', 'trbvm.com',
        'mailnull.com', 'spammotel.com', 'mailbucket.org', 'spaml.de',
        'mailseal.de', 'spamslicer.com', 'mailtemp.info', 'squizzy.de',
        'tempe-mail.com', 'boun.cr', 'deadaddress.com', 'spamdaydream.com',
        'spamex.com', 'spamfree24.org', 'spamgob.com', 'spamkill.info',
        'spamoff.de', 'spamspot.com', 'spamthisplease.com', 'spamtrapped.com',
        'spamzy.com', 'uroid.com', 'veryrealemail.com', 'zoemail.net',
        'throwam.com', '0-mail.com', '027168.com', '0815.ru', '0815.su',
        '0clickemail.com', '0-mail.com', '0815.ru', '0clickemail.com',
        'bugmenot.com', 'humaility.com', 'mailexpire.com', 'mailezee.com',
        'punkass.com', 'put2.net', 'spambob.com', 'spambob.net',
        'spambob.org', 'spamgap.com', 'dispostable.com', 'tempmailo.com',
        'mohmal.com', 'luxusmail.org', 'luxusmail.tk', 'luxusmail.gq',
        'mailnow.top', 'gmal.com', 'gmial.com', 'gmali.com',
        'dropmail.me', 'mailnesia.com', 'mailpoof.com', 'mailscrap.com',
        'mailshell.com', 'mailslapping.com', 'mailsiphon.com', 'mailslite.com',
        'throwam.com', 'tempail.com', 'tempr.email', 'temp-mail.org',
        'temp-mail.ru', 'temp-mail.de', 'tempmail.ninja', 'tempmail.us',
    ];

    // Well-known free email providers (NOT disposable, just free)
    private const FREE_EMAIL_PROVIDERS = [
        'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'aol.com',
        'icloud.com', 'live.com', 'msn.com', 'me.com', 'mac.com',
        'yahoo.co.uk', 'yahoo.co.in', 'yahoo.fr', 'yahoo.de', 'yahoo.es',
        'yahoo.it', 'yahoo.ca', 'yahoo.com.br', 'yahoo.com.au',
        'hotmail.co.uk', 'hotmail.fr', 'hotmail.de', 'hotmail.it', 'hotmail.es',
        'live.co.uk', 'live.fr', 'live.de', 'live.it', 'live.com.au',
        'protonmail.com', 'protonmail.ch', 'pm.me',
        'zoho.com', 'zohomail.com',
        'gmx.com', 'gmx.net', 'gmx.de', 'gmx.at', 'gmx.ch',
        'web.de', 't-online.de', 'freenet.de',
        'mail.ru', 'yandex.ru', 'yandex.com',
        'tutanota.com', 'tutanota.de', 'tuta.io',
        'fastmail.com', 'fastmail.fm',
        'rediffmail.com', 'sify.com',
        'inbox.com', 'usa.com', 'email.com',
    ];

    /**
     * Check if domain is disposable
     */
    public function check(string $domain): array
    {
        $domain = strtolower(trim($domain));

        // Check built-in list first (fastest)
        if (in_array($domain, self::BUILT_IN_DISPOSABLE, true)) {
            return ['is_disposable' => true, 'source' => 'builtin'];
        }

        // Check database (cached in Redis)
        $isDisposable = Cache::remember(
            "disposable:{$domain}",
            self::CACHE_TTL,
            fn () => DB::table('disposable_domains')
                ->where('domain', $domain)
                ->where('is_active', true)
                ->exists()
        );

        return ['is_disposable' => $isDisposable, 'source' => 'database'];
    }

    /**
     * Check if domain is a free email provider
     */
    public function isFreeEmail(string $domain): bool
    {
        $domain = strtolower(trim($domain));

        // Check built-in list
        if (in_array($domain, self::FREE_EMAIL_PROVIDERS, true)) {
            return true;
        }

        // Check database
        return Cache::remember(
            "free_email:{$domain}",
            self::CACHE_TTL,
            fn () => DB::table('free_email_providers')
                ->where('domain', $domain)
                ->where('is_active', true)
                ->exists()
        );
    }

    /**
     * Get bulk disposable domains list (for seeding)
     */
    public function getBuiltInList(): array
    {
        return self::BUILT_IN_DISPOSABLE;
    }

    /**
     * Add domain to disposable list
     */
    public function addDisposableDomain(string $domain, string $source = 'manual'): void
    {
        DB::table('disposable_domains')->updateOrInsert(
            ['domain' => strtolower($domain)],
            ['source' => $source, 'is_active' => true, 'updated_at' => now()]
        );

        // Clear cache
        Cache::forget("disposable:{$domain}");
    }
}
