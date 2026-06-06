<?php

namespace App\Services\Validation;

use App\Models\Domain;
use App\Models\ValidationResult;
use App\Services\Validation\SyntaxValidator;
use App\Services\Validation\DnsValidator;
use App\Services\Validation\SmtpValidator;
use App\Services\Validation\DisposableDetector;
use App\Services\Validation\SpamTrapDetector;
use App\Services\Validation\ScoringEngine;
use App\Services\Validation\MailboxDetector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================
 * Master Email Validation Service
 * Enterprise Email Validation Platform
 *
 * Orchestrates all validation sub-engines:
 * 1. Syntax Validation (RFC compliance)
 * 2. DNS Validation (MX, SPF, DMARC)
 * 3. SMTP Validation (mailbox check)
 * 4. Disposable Detection
 * 5. Spam Trap Detection
 * 6. AI Scoring
 * ============================================================
 */
class EmailValidationService
{
    // Cache TTL for validated emails (24 hours)
    private const CACHE_TTL = 86400;

    // Status constants
    public const STATUS_VALID         = 'valid';
    public const STATUS_INVALID       = 'invalid';
    public const STATUS_RISKY         = 'risky';
    public const STATUS_UNKNOWN       = 'unknown';
    public const STATUS_CATCH_ALL     = 'catch_all';
    public const STATUS_DISPOSABLE    = 'disposable';
    public const STATUS_SPAM_TRAP     = 'spam_trap';
    public const STATUS_UNVERIFIABLE  = 'unverifiable';

    public function __construct(
        private readonly SyntaxValidator    $syntaxValidator,
        private readonly DnsValidator       $dnsValidator,
        private readonly SmtpValidator      $smtpValidator,
        private readonly DisposableDetector $disposableDetector,
        private readonly SpamTrapDetector   $spamTrapDetector,
        private readonly ScoringEngine      $scoringEngine,
        private readonly MailboxDetector    $mailboxDetector,
    ) {}

    /**
     * Validate a single email address
     *
     * @param string $email  The email to validate
     * @param int    $userId The user performing the check
     * @param array  $options Validation options
     * @return array Validation result
     */
    public function validate(string $email, int $userId, array $options = []): array
    {
        $startTime = microtime(true);
        $email     = strtolower(trim($email));

        // --------------------------------------------------------
        // STEP 1: Check cache first (performance optimization)
        // --------------------------------------------------------
        $cacheKey = "email_validation:{$email}";
        if (! ($options['skip_cache'] ?? false)) {
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                Log::debug("Cache hit for email: {$email}");
                return array_merge($cached, [
                    'from_cache'         => true,
                    'validation_time_ms' => (int) ((microtime(true) - $startTime) * 1000),
                ]);
            }
        }

        // Initialize result DTO
        $result = $this->initializeResult($email, $userId);

        // --------------------------------------------------------
        // STEP 2: Syntax Validation (fastest, fail early)
        // --------------------------------------------------------
        $syntaxResult = $this->syntaxValidator->validate($email);
        $result       = array_merge($result, $syntaxResult);

        if (! $syntaxResult['syntax_valid']) {
            $result['status'] = self::STATUS_INVALID;
            $result['score']  = 0;
            return $this->finalizeResult($result, $startTime, $userId, $cacheKey, 300);
        }

        // Extract parts
        [$localPart, $domain] = explode('@', $email, 2);
        $result['local_part'] = $localPart;
        $result['domain']     = $domain;

        // --------------------------------------------------------
        // STEP 3: Disposable Email Detection (fast DB lookup)
        // --------------------------------------------------------
        $disposableResult = $this->disposableDetector->check($domain);
        $result['is_disposable'] = $disposableResult['is_disposable'];

        if ($disposableResult['is_disposable']) {
            $result['status'] = self::STATUS_DISPOSABLE;
            $result['score']  = 5;
            return $this->finalizeResult($result, $startTime, $userId, $cacheKey, 86400);
        }

        // --------------------------------------------------------
        // STEP 4: Spam Trap Detection
        // --------------------------------------------------------
        $spamTrapResult = $this->spamTrapDetector->check($email, $domain);
        $result['is_spam_trap']    = $spamTrapResult['is_spam_trap'];
        $result['is_honeypot']     = $spamTrapResult['is_honeypot'];
        $result['is_toxic_domain'] = $spamTrapResult['is_toxic'];

        if ($spamTrapResult['is_spam_trap'] || $spamTrapResult['is_toxic']) {
            $result['status'] = self::STATUS_SPAM_TRAP;
            $result['score']  = 0;
            return $this->finalizeResult($result, $startTime, $userId, $cacheKey, 86400);
        }

        // --------------------------------------------------------
        // STEP 5: Role-based Detection
        // --------------------------------------------------------
        $result['is_role_based'] = $this->isRoleBased($localPart);

        // --------------------------------------------------------
        // STEP 6: Free Email Detection
        // --------------------------------------------------------
        $result['is_free_email'] = $this->disposableDetector->isFreeEmail($domain);

        // --------------------------------------------------------
        // STEP 7: DNS Validation (MX, A, SPF, DMARC)
        // --------------------------------------------------------
        $dnsResult = $this->dnsValidator->validate($domain);
        $result    = array_merge($result, $dnsResult);

        if (! $dnsResult['mx_found'] && ! $dnsResult['a_record_found']) {
            $result['status'] = self::STATUS_INVALID;
            $result['score']  = 2;
            return $this->finalizeResult($result, $startTime, $userId, $cacheKey, 3600);
        }

        // --------------------------------------------------------
        // STEP 8: Mailbox Provider Detection
        // --------------------------------------------------------
        $providerResult = $this->mailboxDetector->detect($domain, $dnsResult['mx_record'] ?? '');
        $result['mailbox_provider'] = $providerResult['provider'];
        $result['provider_type']    = $providerResult['type'];

        // --------------------------------------------------------
        // STEP 9: SMTP Validation (most expensive operation)
        // --------------------------------------------------------
        $smtpEnabled = $options['smtp_validation'] ?? true;
        if ($smtpEnabled && $dnsResult['mx_found']) {
            $smtpResult = $this->smtpValidator->validate($email, $dnsResult['mx_records'] ?? []);
            $result     = array_merge($result, $smtpResult);
        }

        // --------------------------------------------------------
        // STEP 10: Calculate Final Score
        // --------------------------------------------------------
        $scoreResult          = $this->scoringEngine->calculate($result);
        $result['score']      = $scoreResult['score'];
        $result['score_breakdown'] = $scoreResult['breakdown'];

        // --------------------------------------------------------
        // STEP 11: Determine Final Status
        // --------------------------------------------------------
        $result['status'] = $this->determineFinalStatus($result);

        // Cache result based on status
        $cacheTtl = $this->getCacheTtl($result['status']);

        return $this->finalizeResult($result, $startTime, $userId, $cacheKey, $cacheTtl);
    }

    /**
     * Initialize blank result DTO
     */
    private function initializeResult(string $email, int $userId): array
    {
        return [
            'email'             => $email,
            'local_part'        => '',
            'domain'            => '',
            'user_id'           => $userId,
            'status'            => self::STATUS_UNKNOWN,
            'score'             => 0,
            'syntax_valid'      => false,
            'syntax_error'      => null,
            'mx_found'          => false,
            'mx_record'         => null,
            'mx_records'        => [],
            'mx_priority'       => null,
            'a_record_found'    => false,
            'spf_found'         => false,
            'spf_record'        => null,
            'dmarc_found'       => false,
            'dmarc_record'      => null,
            'smtp_connectable'  => false,
            'smtp_valid'        => false,
            'smtp_banner'       => null,
            'smtp_response'     => null,
            'smtp_response_code'=> null,
            'catch_all'         => false,
            'greylisted'        => false,
            'is_disposable'     => false,
            'is_role_based'     => false,
            'is_free_email'     => false,
            'is_catch_all'      => false,
            'is_spam_trap'      => false,
            'is_honeypot'       => false,
            'is_toxic_domain'   => false,
            'is_recently_active'=> false,
            'mailbox_provider'  => null,
            'provider_type'     => null,
            'score_breakdown'   => [],
            'from_cache'        => false,
            'validation_time_ms'=> 0,
        ];
    }

    /**
     * Determine final validation status based on all checks
     */
    private function determineFinalStatus(array $result): string
    {
        // Hard failures
        if (! $result['syntax_valid'])      return self::STATUS_INVALID;
        if ($result['is_spam_trap'])        return self::STATUS_SPAM_TRAP;
        if ($result['is_disposable'])       return self::STATUS_DISPOSABLE;
        if (! $result['mx_found'] && ! $result['a_record_found']) return self::STATUS_INVALID;

        // SMTP-based decisions
        if (isset($result['smtp_valid'])) {
            if ($result['smtp_valid'] === true)  {
                if ($result['is_catch_all']) return self::STATUS_CATCH_ALL;
                return self::STATUS_VALID;
            }
            if ($result['smtp_valid'] === false) return self::STATUS_INVALID;
        }

        // Risky conditions
        if ($result['is_role_based'] || $result['is_catch_all'] || $result['greylisted']) {
            return self::STATUS_RISKY;
        }

        // No SMTP result available
        if ($result['mx_found']) {
            return self::STATUS_UNKNOWN;
        }

        return self::STATUS_INVALID;
    }

    /**
     * Check if email local part is role-based
     */
    private function isRoleBased(string $localPart): bool
    {
        static $roleKeywords = null;

        if ($roleKeywords === null) {
            $roleKeywords = Cache::remember('role_keywords', 3600, function () {
                return \App\Models\RoleKeyword::where('is_active', true)
                    ->pluck('keyword')
                    ->toArray();
            });
        }

        return in_array(strtolower($localPart), $roleKeywords, true);
    }

    /**
     * Cache TTL based on result confidence
     */
    private function getCacheTtl(string $status): int
    {
        return match ($status) {
            self::STATUS_VALID        => 86400,  // 24h for valid
            self::STATUS_INVALID      => 43200,  // 12h for invalid
            self::STATUS_DISPOSABLE   => 604800, // 7 days for disposable
            self::STATUS_SPAM_TRAP    => 604800, // 7 days for spam traps
            self::STATUS_CATCH_ALL    => 3600,   // 1h for catch-all
            self::STATUS_RISKY        => 7200,   // 2h for risky
            default                   => 1800,   // 30min for unknown
        };
    }

    /**
     * Finalize result: calculate timing, save to cache, save to DB
     */
    private function finalizeResult(
        array  $result,
        float  $startTime,
        int    $userId,
        string $cacheKey,
        int    $cacheTtl
    ): array {
        $result['validation_time_ms'] = (int) ((microtime(true) - $startTime) * 1000);

        // Cache the result (remove user_id and mx_records from cache)
        $cacheData = array_diff_key($result, array_flip(['user_id', 'mx_records']));
        Cache::put($cacheKey, $cacheData, $cacheTtl);

        return $result;
    }

    /**
     * Save validation result to database
     */
    public function saveResult(array $result, ?int $jobId = null): ValidationResult
    {
        return ValidationResult::create([
            'job_id'             => $jobId,
            'user_id'            => $result['user_id'],
            'email'              => $result['email'],
            'local_part'         => $result['local_part'],
            'domain'             => $result['domain'],
            'status'             => $result['status'],
            'score'              => $result['score'],
            'syntax_valid'       => $result['syntax_valid'],
            'syntax_error'       => $result['syntax_error'],
            'mx_found'           => $result['mx_found'],
            'mx_record'          => $result['mx_record'],
            'mx_priority'        => $result['mx_priority'],
            'a_record_found'     => $result['a_record_found'],
            'spf_found'          => $result['spf_found'],
            'spf_record'         => $result['spf_record'],
            'dmarc_found'        => $result['dmarc_found'],
            'dmarc_record'       => $result['dmarc_record'],
            'smtp_connectable'   => $result['smtp_connectable'],
            'smtp_valid'         => $result['smtp_valid'],
            'smtp_banner'        => $result['smtp_banner'],
            'smtp_response'      => $result['smtp_response'],
            'smtp_response_code' => $result['smtp_response_code'],
            'catch_all'          => $result['catch_all'],
            'greylisted'         => $result['greylisted'],
            'is_disposable'      => $result['is_disposable'],
            'is_role_based'      => $result['is_role_based'],
            'is_free_email'      => $result['is_free_email'],
            'is_catch_all'       => $result['is_catch_all'],
            'is_spam_trap'       => $result['is_spam_trap'],
            'is_honeypot'        => $result['is_honeypot'],
            'is_toxic_domain'    => $result['is_toxic_domain'],
            'mailbox_provider'   => $result['mailbox_provider'],
            'provider_type'      => $result['provider_type'],
            'score_breakdown'    => $result['score_breakdown'],
            'validation_time_ms' => $result['validation_time_ms'],
            'from_cache'         => $result['from_cache'],
        ]);
    }
}
