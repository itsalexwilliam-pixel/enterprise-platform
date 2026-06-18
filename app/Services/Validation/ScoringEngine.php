<?php

namespace App\Services\Validation;

/**
 * ============================================================
 * Email Quality Scoring Engine
 * Produces a 0-100 score based on multiple factors
 *
 * Score Breakdown:
 * - SMTP Success:        35 points
 * - MX Health:          20 points
 * - Domain Reputation:  15 points
 * - SPF/DMARC:          10 points
 * - Disposable Penalty: -50 points
 * - Catch-All Penalty:  -20 points
 * - Role-Based Penalty:  -10 points
 * - Spam Trap Penalty:  -100 points (hard zero)
 * ============================================================
 */
class ScoringEngine
{
    // Scoring weights
    private const WEIGHTS = [
        'smtp_valid'      => 35,
        'mx_found'        => 20,
        'a_record'        => 5,
        'spf_found'       => 7,
        'dmarc_found'     => 8,
        'base'            => 25, // Everyone starts with 25
    ];

    // Providers that block SMTP probing — valid MX means likely deliverable
    private const TRUSTED_PROVIDERS = [
        'gmail', 'outlook', 'yahoo', 'office365', 'icloud',
        'google_workspace', 'protonmail', 'fastmail', 'zoho', 'yandex', 'mailru',
    ];

    // Penalties
    private const PENALTIES = [
        'is_spam_trap'    => -100, // Hard zero
        'is_disposable'   => -50,
        'is_catch_all'    => -20,
        'is_role_based'   => -10,
        'greylisted'      => -5,
        'no_spf'          => -5,
        'no_dmarc'        => -3,
    ];

    /**
     * Calculate quality score for a validation result
     *
     * @param array $result Validation result data
     * @return array ['score' => int, 'breakdown' => array]
     */
    public function calculate(array $result): array
    {
        $breakdown = [];
        $score     = 0;

        // --------------------------------------------------------
        // HARD FAILURES (immediately return 0)
        // --------------------------------------------------------
        if ($result['is_spam_trap'] ?? false) {
            return [
                'score'     => 0,
                'breakdown' => ['spam_trap_penalty' => -100],
            ];
        }

        if (! ($result['syntax_valid'] ?? false)) {
            return [
                'score'     => 0,
                'breakdown' => ['invalid_syntax' => -100],
            ];
        }

        if ($result['is_disposable'] ?? false) {
            return [
                'score'     => 5,
                'breakdown' => ['disposable_penalty' => -95],
            ];
        }

        // --------------------------------------------------------
        // BASE SCORE
        // --------------------------------------------------------
        $score += self::WEIGHTS['base'];
        $breakdown['base'] = self::WEIGHTS['base'];

        // --------------------------------------------------------
        // MX RECORD FOUND (+20)
        // --------------------------------------------------------
        if ($result['mx_found'] ?? false) {
            $score += self::WEIGHTS['mx_found'];
            $breakdown['mx_found'] = self::WEIGHTS['mx_found'];
        } else {
            if ($result['a_record_found'] ?? false) {
                $score += self::WEIGHTS['a_record'];
                $breakdown['a_record'] = self::WEIGHTS['a_record'];
            }
        }

        // --------------------------------------------------------
        // SMTP VALIDATION RESULT
        // --------------------------------------------------------
        $provider        = $result['mailbox_provider'] ?? 'other';
        $isTrustedProvider = in_array($provider, self::TRUSTED_PROVIDERS, true);

        if (isset($result['smtp_valid'])) {
            if ($result['smtp_valid'] === true) {
                // Confirmed valid by SMTP RCPT TO
                $score += self::WEIGHTS['smtp_valid'];
                $breakdown['smtp_valid'] = self::WEIGHTS['smtp_valid'];

            } elseif ($result['smtp_valid'] === false) {
                if ($result['smtp_connectable'] ?? false) {
                    // Connected but RCPT TO rejected
                    if ($isTrustedProvider || ($result['is_free_email'] ?? false)) {
                        // Major providers actively block probing — soft penalty only
                        $score = max(0, $score - 10);
                        $breakdown['smtp_probe_blocked'] = -10;
                    } else {
                        // Small/corporate server confirmed rejection — hard penalty
                        $score = max(0, $score - 60);
                        $breakdown['smtp_invalid'] = -60;
                    }
                } else {
                    // Could not connect (port 25 + 587 blocked — typical AWS EC2).
                    // For trusted providers with valid MX, award partial SMTP score
                    // since the provider is known to be real and block probing.
                    if ($isTrustedProvider) {
                        $partial = (int) (self::WEIGHTS['smtp_valid'] * 0.6); // 21 pts
                        $score  += $partial;
                        $breakdown['smtp_trusted_no_connect'] = $partial;
                    }
                    // Unknown provider — no points, no penalty (cannot determine)
                }

            } else {
                // smtp_valid === null — greylisted / temporary failure
                // Give partial credit — address likely exists
                $partial = (int) (self::WEIGHTS['smtp_valid'] * 0.4); // 14 pts
                $score  += $partial;
                $breakdown['smtp_greylisted'] = $partial;
            }
        } elseif ($isTrustedProvider && ($result['mx_found'] ?? false)) {
            // No SMTP attempt made but trusted provider with MX — partial credit
            $partial = (int) (self::WEIGHTS['smtp_valid'] * 0.5); // 17 pts
            $score  += $partial;
            $breakdown['smtp_trusted_skipped'] = $partial;
        }

        // --------------------------------------------------------
        // SPF RECORD (+7)
        // --------------------------------------------------------
        if ($result['spf_found'] ?? false) {
            $score += self::WEIGHTS['spf_found'];
            $breakdown['spf_found'] = self::WEIGHTS['spf_found'];
        }

        // --------------------------------------------------------
        // DMARC RECORD (+8)
        // --------------------------------------------------------
        if ($result['dmarc_found'] ?? false) {
            $score += self::WEIGHTS['dmarc_found'];
            $breakdown['dmarc_found'] = self::WEIGHTS['dmarc_found'];
        }

        // --------------------------------------------------------
        // WELL-KNOWN PROVIDER BONUS (+5)
        // --------------------------------------------------------
        $provider = $result['mailbox_provider'] ?? null;
        if (in_array($provider, ['gmail', 'outlook', 'yahoo', 'office365', 'icloud'], true)) {
            $score += 5;
            $breakdown['trusted_provider'] = 5;
        }

        // --------------------------------------------------------
        // PENALTIES
        // --------------------------------------------------------
        if ($result['is_catch_all'] ?? false) {
            $score += self::PENALTIES['is_catch_all'];
            $breakdown['catch_all_penalty'] = self::PENALTIES['is_catch_all'];
        }

        if ($result['is_role_based'] ?? false) {
            $score += self::PENALTIES['is_role_based'];
            $breakdown['role_based_penalty'] = self::PENALTIES['is_role_based'];
        }

        if ($result['greylisted'] ?? false) {
            $score += self::PENALTIES['greylisted'];
            $breakdown['greylist_penalty'] = self::PENALTIES['greylisted'];
        }

        if (! ($result['spf_found'] ?? false)) {
            $score += self::PENALTIES['no_spf'];
            $breakdown['no_spf_penalty'] = self::PENALTIES['no_spf'];
        }

        if (! ($result['dmarc_found'] ?? false)) {
            $score += self::PENALTIES['no_dmarc'];
            $breakdown['no_dmarc_penalty'] = self::PENALTIES['no_dmarc'];
        }

        // Domain reputation adjustment
        $domainScore = $this->getDomainReputationScore($result['domain'] ?? '');
        if ($domainScore > 0) {
            $adjustment = (int) (($domainScore - 50) / 10); // -5 to +5
            $score     += $adjustment;
            $breakdown['domain_reputation'] = $adjustment;
        }

        // Clamp to 0-100
        $score = max(0, min(100, $score));

        return [
            'score'     => $score,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Get domain reputation score from cache/DB
     */
    private function getDomainReputationScore(string $domain): int
    {
        if (empty($domain)) return 50;

        return \Illuminate\Support\Facades\Cache::remember(
            "domain_rep:{$domain}",
            3600,
            fn () => \App\Models\Domain::where('domain', $domain)->value('reputation_score') ?? 50
        );
    }

    /**
     * Get human-readable risk level
     */
    public static function getRiskLevel(int $score): string
    {
        return match(true) {
            $score >= 80 => 'low',
            $score >= 50 => 'medium',
            $score >= 20 => 'high',
            default      => 'very_high',
        };
    }

    /**
     * Get deliverability prediction
     */
    public static function getDeliverability(int $score): string
    {
        return match(true) {
            $score >= 80 => 'excellent',
            $score >= 60 => 'good',
            $score >= 40 => 'fair',
            $score >= 20 => 'poor',
            default      => 'very_poor',
        };
    }
}
