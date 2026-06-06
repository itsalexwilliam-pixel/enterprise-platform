<?php

namespace App\Services\Validation;

/**
 * ============================================================
 * Syntax Validator
 * RFC 5321, RFC 5322 Compliance Checker
 * ============================================================
 */
class SyntaxValidator
{
    // Valid TLDs list (top 1000+ for performance, use IANA list in production)
    private const KNOWN_INVALID_TLDS = [
        'invalid', 'test', 'localhost', 'local', 'example',
        'internal', 'intranet', 'lan', 'private', 'corp',
    ];

    // Maximum lengths per RFC 5321
    private const MAX_EMAIL_LENGTH  = 320;
    private const MAX_LOCAL_LENGTH  = 64;
    private const MAX_DOMAIN_LENGTH = 255;

    /**
     * Validate email syntax
     *
     * @return array ['syntax_valid' => bool, 'syntax_error' => string|null]
     */
    public function validate(string $email): array
    {
        // Basic length check
        if (strlen($email) > self::MAX_EMAIL_LENGTH) {
            return $this->fail('Email exceeds maximum length of 320 characters');
        }

        if (empty($email)) {
            return $this->fail('Email address is empty');
        }

        // Must contain exactly one @ symbol
        $atCount = substr_count($email, '@');
        if ($atCount === 0) {
            return $this->fail('Missing @ symbol');
        }
        if ($atCount > 1) {
            return $this->fail('Multiple @ symbols found');
        }

        [$local, $domain] = explode('@', $email, 2);

        // --------------------------------------------------------
        // Validate local part (before @)
        // --------------------------------------------------------
        if (empty($local)) {
            return $this->fail('Local part (before @) is empty');
        }

        if (strlen($local) > self::MAX_LOCAL_LENGTH) {
            return $this->fail('Local part exceeds 64 characters');
        }

        // Cannot start or end with a dot
        if (str_starts_with($local, '.') || str_ends_with($local, '.')) {
            return $this->fail('Local part cannot start or end with a dot');
        }

        // Cannot have consecutive dots
        if (str_contains($local, '..')) {
            return $this->fail('Local part contains consecutive dots');
        }

        // Check valid characters in local part (RFC 5321)
        // Allowed: a-z A-Z 0-9 ! # $ % & ' * + - / = ? ^ _ ` { | } ~ .
        if (! preg_match('/^[a-zA-Z0-9!#$%&\'*+\-\/=?^_`{|}~.]+$/', $local)) {
            // Check for quoted strings (allowed per RFC)
            if (! $this->isValidQuotedLocal($local)) {
                return $this->fail('Local part contains invalid characters');
            }
        }

        // --------------------------------------------------------
        // Validate domain part (after @)
        // --------------------------------------------------------
        if (empty($domain)) {
            return $this->fail('Domain part (after @) is empty');
        }

        if (strlen($domain) > self::MAX_DOMAIN_LENGTH) {
            return $this->fail('Domain exceeds 255 characters');
        }

        // Cannot start or end with hyphen or dot
        if (str_starts_with($domain, '-') || str_ends_with($domain, '-')) {
            return $this->fail('Domain cannot start or end with a hyphen');
        }

        if (str_starts_with($domain, '.') || str_ends_with($domain, '.')) {
            return $this->fail('Domain cannot start or end with a dot');
        }

        // Must have at least one dot (TLD check)
        if (! str_contains($domain, '.')) {
            return $this->fail('Domain must contain a TLD (e.g., .com)');
        }

        // Check for consecutive dots or hyphens
        if (str_contains($domain, '..')) {
            return $this->fail('Domain contains consecutive dots');
        }

        // Validate domain characters (RFC 1035)
        $domainParts = explode('.', $domain);
        foreach ($domainParts as $part) {
            if (empty($part)) {
                return $this->fail('Domain contains empty label');
            }

            if (strlen($part) > 63) {
                return $this->fail("Domain label '{$part}' exceeds 63 characters");
            }

            if (! preg_match('/^[a-zA-Z0-9]([a-zA-Z0-9\-]*[a-zA-Z0-9])?$/', $part)) {
                // Allow internationalized domains (IDN)
                if (! preg_match('/^xn--/', $part) && ! $this->isValidIdnLabel($part)) {
                    return $this->fail("Domain label '{$part}' contains invalid characters");
                }
            }
        }

        // Validate TLD
        $tld = strtolower(end($domainParts));

        if (strlen($tld) < 2) {
            return $this->fail('TLD must be at least 2 characters');
        }

        // Check against known invalid TLDs
        if (in_array($tld, self::KNOWN_INVALID_TLDS, true)) {
            return $this->fail("TLD '{$tld}' is not valid for real email addresses");
        }

        // All checks passed
        return [
            'syntax_valid' => true,
            'syntax_error' => null,
        ];
    }

    /**
     * Check if local part is a valid quoted string
     * e.g., "john doe"@example.com is valid per RFC 5321
     */
    private function isValidQuotedLocal(string $local): bool
    {
        if (! str_starts_with($local, '"') || ! str_ends_with($local, '"')) {
            return false;
        }

        $inner = substr($local, 1, -1);
        return preg_match('/^[^\x00-\x1F\x7F"\\\\]*(\\\\.)*[^\x00-\x1F\x7F"\\\\]*$/', $inner) === 1;
    }

    /**
     * Basic IDN label validation
     */
    private function isValidIdnLabel(string $label): bool
    {
        // Accept unicode characters for internationalized domains
        return preg_match('/^[\p{L}\p{N}][\p{L}\p{N}\-]*[\p{L}\p{N}]$/u', $label) === 1
            || preg_match('/^[\p{L}\p{N}]$/u', $label) === 1;
    }

    private function fail(string $error): array
    {
        return [
            'syntax_valid' => false,
            'syntax_error' => $error,
        ];
    }
}
