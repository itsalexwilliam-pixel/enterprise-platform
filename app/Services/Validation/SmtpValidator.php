<?php

namespace App\Services\Validation;

use App\Models\SmtpServer;
use App\Models\SmtpLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * ============================================================
 * SMTP Validator
 * Performs real SMTP conversations to verify email existence
 *
 * Process:
 * 1. Connect to MX server on port 25
 * 2. Send EHLO
 * 3. Send MAIL FROM
 * 4. Send RCPT TO  ← key check
 * 5. Parse response code
 *
 * Response Codes:
 * 2xx = Valid
 * 4xx = Temporary failure (greylist/retry)
 * 5xx = Permanent failure (invalid)
 * ============================================================
 */
class SmtpValidator
{
    private const SMTP_PORT         = 25;
    private const CONNECT_TIMEOUT   = 3;    // seconds — very short; EC2 blocks port 25 outbound by default
    private const READ_TIMEOUT      = 5;    // seconds
    private const MAX_RETRIES       = 1;    // single attempt — retrying doubles latency on blocked ports
    private const MAX_MX_TO_TRY     = 2;    // only try top 2 MX records max — if port 25 is blocked network-wide, trying all records wastes time

    // Catch-all detection: test a random email on the domain
    private const CATCH_ALL_TEST_PREFIX = 'this-email-should-not-exist-xyz123abc';

    private string $heloDomain;
    private string $fromEmail;

    public function __construct()
    {
        $this->heloDomain = config('validation.smtp_helo_domain', 'mail.validator.com');
        $this->fromEmail  = config('validation.smtp_from_email', 'verify@validator.com');
    }

    /**
     * Validate email via SMTP
     *
     * @param string $email     Email to validate
     * @param array  $mxRecords Sorted MX records
     * @return array SMTP validation result
     */
    public function validate(string $email, array $mxRecords = []): array
    {
        $result = [
            'smtp_connectable'  => false,
            'smtp_valid'        => false,
            'smtp_banner'       => null,
            'smtp_response'     => null,
            'smtp_response_code'=> null,
            'catch_all'         => false,
            'is_catch_all'      => false,
            'greylisted'        => false,
        ];

        if (empty($mxRecords)) {
            return $result;
        }

        // Try top MX records (by priority order) — limit attempts to avoid timeout
        $mxToTry = array_slice($mxRecords, 0, self::MAX_MX_TO_TRY);

        foreach ($mxToTry as $mx) {
            $mxHost = $mx['host'];
            $mxIp   = gethostbyname($mxHost);

            if ($mxIp === $mxHost) {
                // Could not resolve MX hostname
                continue;
            }

            // Attempt SMTP validation
            $smtpResult = $this->attemptSmtpValidation($email, $mxHost, $mxIp);

            if ($smtpResult['connected']) {
                $result = array_merge($result, $smtpResult);
                $result['smtp_connectable'] = true;

                // If we got a definitive answer, stop trying MX records
                if ($smtpResult['smtp_response_code'] !== null) {
                    break;
                }
            } else {
                // Could not connect to this MX at all (port 25 + 587 both blocked).
                // If outbound port 25 is blocked on our server (e.g. AWS EC2),
                // every MX server for this domain will fail the same way.
                // Stop trying further MX records to avoid cascading timeouts.
                break;
            }
        }

        // --------------------------------------------------------
        // Catch-All Detection
        // If email appears valid, test a random address on the domain
        // --------------------------------------------------------
        if ($result['smtp_valid'] === true) {
            [$local, $domain] = explode('@', $email, 2);
            $testEmail        = self::CATCH_ALL_TEST_PREFIX . '@' . $domain;

            $catchAllResult = $this->checkCatchAll($testEmail, $mxRecords);
            if ($catchAllResult) {
                $result['catch_all']   = true;
                $result['is_catch_all']= true;
            }
        }

        return $result;
    }

    /**
     * Attempt SMTP conversation with a single MX server
     * Tries port 25 first, falls back to port 587 with STARTTLS.
     */
    private function attemptSmtpValidation(string $email, string $mxHost, string $mxIp): array
    {
        $conversation = [];
        $usedPort     = self::SMTP_PORT;
        $result = [
            'connected'          => false,
            'smtp_valid'         => false,
            'smtp_banner'        => null,
            'smtp_response'      => null,
            'smtp_response_code' => null,
            'greylisted'         => false,
        ];

        $attempt = 0;
        while ($attempt < self::MAX_RETRIES) {
            $attempt++;
            $socket = null;

            try {
                // ------------------------------------------------
                // Connect to SMTP server — port 25 first, then 587
                // ------------------------------------------------
                $socket = @fsockopen($mxIp, 25, $errno, $errstr, self::CONNECT_TIMEOUT);

                if (! $socket) {
                    // Port 25 blocked (common on AWS EC2) — try 587
                    $socket = @fsockopen($mxIp, 587, $errno, $errstr, self::CONNECT_TIMEOUT);
                    if (! $socket) {
                        Log::debug("Cannot connect to {$mxHost} ({$mxIp}) on port 25 or 587: {$errstr}");
                        break;
                    }
                    $usedPort = 587;
                }

                stream_set_timeout($socket, self::READ_TIMEOUT);
                $result['connected'] = true;

                // Read banner
                $banner = $this->readResponse($socket);
                $result['smtp_banner'] = $this->extractText($banner);
                $conversation[]        = "< {$banner}";

                $bannerCode = $this->getResponseCode($banner);
                if ($bannerCode !== 220) {
                    // 421 = server busy, treat as greylist
                    if ($bannerCode === 421) {
                        $result['greylisted']        = true;
                        $result['smtp_valid']         = null;
                        $result['smtp_response_code'] = 421;
                    }
                    break;
                }

                // ------------------------------------------------
                // EHLO / HELO
                // ------------------------------------------------
                $ehloResp = $this->sendCommand($socket, "EHLO {$this->heloDomain}", $conversation);
                $ehloCode = $this->getResponseCode($ehloResp);

                if ($ehloCode !== 250) {
                    $heloResp = $this->sendCommand($socket, "HELO {$this->heloDomain}", $conversation);
                    if ($this->getResponseCode($heloResp) !== 250) {
                        break;
                    }
                    $ehloResp = $heloResp;
                }

                // ------------------------------------------------
                // STARTTLS — upgrade if on port 587 and server supports it
                // ------------------------------------------------
                if ($usedPort === 587 && str_contains(strtolower($ehloResp), 'starttls')) {
                    $tlsResp = $this->sendCommand($socket, 'STARTTLS', $conversation);
                    if ($this->getResponseCode($tlsResp) === 220) {
                        // Upgrade the plain socket to TLS
                        if (@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                            // Re-issue EHLO after TLS upgrade
                            $this->sendCommand($socket, "EHLO {$this->heloDomain}", $conversation);
                        }
                    }
                }

                // ------------------------------------------------
                // MAIL FROM
                // ------------------------------------------------
                $fromResp = $this->sendCommand(
                    $socket,
                    "MAIL FROM:<{$this->fromEmail}>",
                    $conversation
                );

                if ($this->getResponseCode($fromResp) !== 250) {
                    break; // Server rejected our FROM address
                }

                // ------------------------------------------------
                // RCPT TO  ← The key validation step
                // ------------------------------------------------
                $rcptResp = $this->sendCommand(
                    $socket,
                    "RCPT TO:<{$email}>",
                    $conversation
                );

                $code = $this->getResponseCode($rcptResp);
                $result['smtp_response_code'] = $code;
                $result['smtp_response']      = $this->extractText($rcptResp);

                if ($code >= 200 && $code < 300) {
                    // 2xx = Mailbox accepted
                    $result['smtp_valid'] = true;

                } elseif ($code === 452 || $code === 552) {
                    // Mailbox full — the address EXISTS, just can't receive right now
                    $result['smtp_valid'] = true;

                } elseif ($code >= 400 && $code < 500) {
                    // 4xx = Temporary failure (greylist, rate limit, try later)
                    // 421 = service unavailable, 450/451 = greylist, 452 = mailbox full
                    $result['greylisted'] = true;
                    $result['smtp_valid'] = null; // Cannot determine — treat as unknown

                } elseif ($code >= 500 && $code < 600) {
                    // 5xx = Permanent failure
                    // 550 = mailbox does not exist
                    // 551 = user not local
                    // 553 = mailbox name not allowed
                    // 554 = transaction failed / policy rejection
                    $result['smtp_valid'] = false;

                } else {
                    $result['smtp_valid'] = null; // No useful code
                }

                // QUIT gracefully
                $this->sendCommand($socket, 'QUIT', $conversation);
                break;

            } catch (\Exception $e) {
                Log::warning("SMTP validation error for {$email}: " . $e->getMessage());

            } finally {
                if ($socket) {
                    @fclose($socket);
                }
            }
        }

        // Log conversation to database
        $this->logConversation($email, $mxHost, $mxIp, $conversation, $result, $usedPort);

        return $result;
    }

    /**
     * Test catch-all by sending RCPT TO a definitely non-existent address
     */
    private function checkCatchAll(string $testEmail, array $mxRecords): bool
    {
        $cacheKey = 'catch_all:' . explode('@', $testEmail, 2)[1];
        return Cache::remember($cacheKey, 3600, function () use ($testEmail, $mxRecords) {
            if (empty($mxRecords)) return false;

            $mx     = $mxRecords[0];
            $mxIp   = gethostbyname($mx['host']);
            $socket = null;

            try {
                $socket = @fsockopen($mxIp, self::SMTP_PORT, $errno, $errstr, self::CONNECT_TIMEOUT);
                if (! $socket) return false;

                stream_set_timeout($socket, self::READ_TIMEOUT);
                $dummy = [];

                $this->readResponse($socket); // Banner
                $this->sendCommand($socket, "EHLO {$this->heloDomain}", $dummy);
                $this->sendCommand($socket, "MAIL FROM:<{$this->fromEmail}>", $dummy);

                $response = $this->sendCommand($socket, "RCPT TO:<{$testEmail}>", $dummy);
                $this->sendCommand($socket, 'QUIT', $dummy);

                $code = $this->getResponseCode($response);
                return $code >= 200 && $code < 300; // Accepted = catch-all

            } catch (\Exception $e) {
                return false;
            } finally {
                if ($socket) @fclose($socket);
            }
        });
    }

    /**
     * Send SMTP command and read response
     */
    private function sendCommand($socket, string $command, array &$conversation): string
    {
        fwrite($socket, $command . "\r\n");
        $conversation[] = "> {$command}";

        $response = $this->readResponse($socket);
        $conversation[] = "< {$response}";

        return $response;
    }

    /**
     * Read full SMTP response (handles multi-line)
     */
    private function readResponse($socket): string
    {
        $response = '';
        $timeout  = time() + self::READ_TIMEOUT;

        while (! feof($socket) && time() < $timeout) {
            $line = fgets($socket, 4096);
            if ($line === false) break;

            $response .= $line;

            // SMTP multi-line responses have '-' after code, single line uses ' '
            if (strlen($line) > 3 && $line[3] === ' ') {
                break; // Last line of response
            }

            if (strlen($line) > 3 && $line[3] === '-') {
                continue; // Multi-line, read more
            }

            break;
        }

        return trim($response);
    }

    /**
     * Extract numeric response code
     */
    private function getResponseCode(string $response): int
    {
        if (preg_match('/^(\d{3})/', $response, $matches)) {
            return (int) $matches[1];
        }
        return 0;
    }

    /**
     * Extract human-readable text from SMTP response
     */
    private function extractText(string $response): string
    {
        // Handle multi-line responses
        $lines = explode("\n", $response);
        $texts = [];
        foreach ($lines as $line) {
            if (strlen($line) > 4) {
                $texts[] = trim(substr($line, 4));
            }
        }
        return implode(' | ', $texts);
    }

    /**
     * Log SMTP conversation to database for debugging
     */
    private function logConversation(
        string $email,
        string $mxHost,
        string $mxIp,
        array  $conversation,
        array  $result,
        int    $port = self::SMTP_PORT
    ): void {
        try {
            // After STARTTLS the socket data is binary (encrypted).
            // MySQL utf8/utf8mb4 cannot store arbitrary binary, so we strip
            // any non-printable / non-ASCII bytes before persisting.
            $rawConversation = implode("\n", $conversation);
            // Keep only printable ASCII + tab + LF + CR; replace anything else with '?'
            $safeConversation = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '?', $rawConversation);
            // Also cap at 10 000 chars so very long SMTP sessions don't overflow TEXT columns
            $safeConversation = mb_substr($safeConversation, 0, 10000);

            SmtpLog::create([
                'email'               => $email,
                'mx_host'             => $mxHost,
                'mx_ip'               => $mxIp,
                'port'                => $port,
                'conversation'        => $safeConversation,
                'connection_success'  => $result['connected'],
                'rcpt_to_response'    => $result['smtp_response'],
                'rcpt_to_code'        => $result['smtp_response_code'],
                'duration_ms'         => 0,
                'created_at'          => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to log SMTP conversation: " . $e->getMessage());
        }
    }
}
