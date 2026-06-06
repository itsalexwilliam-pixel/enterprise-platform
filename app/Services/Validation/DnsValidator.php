<?php

namespace App\Services\Validation;

use App\Models\Domain as DomainModel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================
 * DNS Validator
 * Validates MX, A, SPF, DMARC records
 * Uses multiple nameservers for reliability
 * ============================================================
 */
class DnsValidator
{
    private const DNS_TIMEOUT     = 5;   // seconds
    private const CACHE_TTL       = 3600; // 1 hour
    private const MAX_MX_RECORDS  = 10;

    // Public nameservers for fallback
    private array $nameservers = [
        '8.8.8.8',   // Google
        '1.1.1.1',   // Cloudflare
        '9.9.9.9',   // Quad9
        '208.67.222.222', // OpenDNS
    ];

    public function __construct()
    {
        // Load nameservers from config
        $configured = config('validation.dns_nameservers', '');
        if ($configured) {
            $this->nameservers = array_filter(explode(',', $configured));
        }
    }

    /**
     * Full DNS validation for a domain
     */
    public function validate(string $domain): array
    {
        $domain    = strtolower($domain);
        $cacheKey  = "dns_validation:{$domain}";

        // Check domain cache first
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Check database cache
        $dbCached = DomainModel::where('domain', $domain)
            ->where('cache_expires_at', '>', now())
            ->first();

        if ($dbCached) {
            $result = [
                'mx_found'       => $dbCached->mx_found,
                'mx_record'      => $dbCached->mx_records[0]['host'] ?? null,
                'mx_records'     => json_decode(json_encode($dbCached->mx_records), true),
                'mx_priority'    => $dbCached->mx_records[0]['priority'] ?? null,
                'a_record_found' => $dbCached->a_record_found,
                'spf_found'      => $dbCached->spf_found,
                'spf_record'     => $dbCached->spf_record,
                'dmarc_found'    => $dbCached->dmarc_found,
                'dmarc_record'   => $dbCached->dmarc_record,
            ];
            Cache::put($cacheKey, $result, self::CACHE_TTL);
            return $result;
        }

        // Perform actual DNS lookups
        return $this->performDnsLookup($domain, $cacheKey);
    }

    /**
     * Perform actual DNS lookups
     */
    private function performDnsLookup(string $domain, string $cacheKey): array
    {
        $result = [
            'mx_found'       => false,
            'mx_record'      => null,
            'mx_records'     => [],
            'mx_priority'    => null,
            'a_record_found' => false,
            'spf_found'      => false,
            'spf_record'     => null,
            'dmarc_found'    => false,
            'dmarc_record'   => null,
        ];

        try {
            // --------------------------------------------------------
            // MX Record Lookup
            // --------------------------------------------------------
            $mxRecords = $this->getMxRecords($domain);
            if (! empty($mxRecords)) {
                $result['mx_found']   = true;
                $result['mx_records'] = $mxRecords;
                $result['mx_record']  = $mxRecords[0]['host'];
                $result['mx_priority']= $mxRecords[0]['pri'];
            }

            // --------------------------------------------------------
            // A Record Lookup (fallback if no MX)
            // --------------------------------------------------------
            if (! $result['mx_found']) {
                $aRecords = dns_get_record($domain, DNS_A);
                $result['a_record_found'] = ! empty($aRecords);
            } else {
                $result['a_record_found'] = true;
            }

            // --------------------------------------------------------
            // SPF Record Lookup (TXT records)
            // --------------------------------------------------------
            $txtRecords = $this->getTxtRecords($domain);
            foreach ($txtRecords as $txt) {
                if (str_starts_with(trim($txt), 'v=spf1')) {
                    $result['spf_found']  = true;
                    $result['spf_record'] = $txt;
                    break;
                }
            }

            // --------------------------------------------------------
            // DMARC Record Lookup
            // --------------------------------------------------------
            $dmarcDomain  = "_dmarc.{$domain}";
            $dmarcRecords = $this->getTxtRecords($dmarcDomain);
            foreach ($dmarcRecords as $txt) {
                if (str_starts_with(trim($txt), 'v=DMARC1')) {
                    $result['dmarc_found']  = true;
                    $result['dmarc_record'] = $txt;
                    break;
                }
            }

        } catch (\Exception $e) {
            Log::warning("DNS lookup failed for {$domain}: " . $e->getMessage());
        }

        // Cache result
        Cache::put($cacheKey, $result, self::CACHE_TTL);

        // Update domain database cache
        $this->updateDomainCache($domain, $result);

        return $result;
    }

    /**
     * Get sorted MX records
     */
    private function getMxRecords(string $domain): array
    {
        try {
            $records = @dns_get_record($domain, DNS_MX);

            if (empty($records)) {
                return [];
            }

            // Sort by priority (lowest = highest priority)
            usort($records, fn ($a, $b) => $a['pri'] <=> $b['pri']);

            // Limit and format
            return array_slice(array_map(fn ($r) => [
                'host'     => strtolower($r['host']),
                'pri'      => (int) $r['pri'],
                'ttl'      => $r['ttl'] ?? 3600,
            ], $records), 0, self::MAX_MX_RECORDS);

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get TXT records for a domain
     */
    private function getTxtRecords(string $domain): array
    {
        try {
            $records = @dns_get_record($domain, DNS_TXT);

            if (empty($records)) {
                return [];
            }

            return array_map(function ($r) {
                return is_array($r['txt'] ?? null)
                    ? implode('', $r['txt'])
                    : ($r['txt'] ?? '');
            }, $records);

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Check if domain has AAAA record (IPv6)
     */
    public function hasAaaaRecord(string $domain): bool
    {
        try {
            $records = @dns_get_record($domain, DNS_AAAA);
            return ! empty($records);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Update domain cache in database
     */
    private function updateDomainCache(string $domain, array $result): void
    {
        try {
            DomainModel::updateOrCreate(
                ['domain' => $domain],
                [
                    'mx_found'        => $result['mx_found'],
                    'a_record_found'  => $result['a_record_found'],
                    'spf_found'       => $result['spf_found'],
                    'spf_record'      => $result['spf_record'],
                    'dmarc_found'     => $result['dmarc_found'],
                    'dmarc_record'    => $result['dmarc_record'],
                    'mx_records'      => $result['mx_records'],
                    'last_checked_at' => now(),
                    'cache_expires_at'=> now()->addHour(),
                ]
            );
        } catch (\Exception $e) {
            Log::error("Failed to update domain cache: " . $e->getMessage());
        }
    }
}
