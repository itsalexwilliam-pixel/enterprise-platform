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
    private const CONNECT_TIMEOUT   = 4;    // seconds — short; EC2 blocks port 25 outbound by default
    private const READ_TIMEOUT      = 6;    // seconds
    private const MAX_RETRIES       = 1;    // single attempt — retrying doubles latency on blocked ports

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

        // Try each MX record (by priority order)
        foreach ($mxRecords as $mx) {
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
     */
    private function attemptSmtpValidation(string $email, string $mxHost, string $mxIp): array
    {
        $conversation = [];
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
                // Connect to SMTP server
                // ------------------------------------------------
                $socket = @fsockopen(
                    $mxIp,
                    self::SMTP_PORT,
                    $errno,
                    $errstr,
                    self::CONNECT_TIMEOUT
                );

                if (! $socket) {
                    // Try alternate port 587 if 25 is blocked
                    $socket = @fsockopen($mxIp, 587, $errno, $errstr, self::CONNECT_TIMEOUT);
                    if (! $socket) {
                        Log::debug("Cannot connect to {$mxHost} ({$mxIp}): {$errstr}");
                        break; // Connection refused, skip this MX
                    }
                }

                stream_set_timeout($socket, self::READ_TIMEOUT);
                $result['connected'] = true;

                // Read banner
                $banner = $this->readResponse($socket);
                $result['smtp_banner'] = $this->extractText($banner);
                $conversation[]        = "< {$banner}";

                if ($this->getResponseCode($banner) !== 220) {
                    break; // Server not ready
                }

                // ------------------------------------------------
                // EHLO / HELO
                // ------------------------------------------------
                $response = $this->sendCommand($socket, "EHLO {$this->heloDomain}", $conversation);
                if ($this->getResponseCode($response) !== 250) {
                    // Fallback to HELO
                    $response = $this->sendCommand($socket, "HELO {$this->heloDomain}", $conversation);
                    if ($this->getResponseCode($response) !== 250) {
                        break;
                    }
                }

                // ------------------------------------------------
                // MAIL FROM
                // ------------------------------------------------
                $response = $this->sendCommand(
                    $socket,
                    "MAIL FROM:<{$this->fromEmail}>",
                    $conversation
                );

                if ($this->getResponseCode($response) !== 250) {
                    // Server rejected our from address
                    break;
                }

                // ------------------------------------------------
                // RCPT TO  ← The key validation step
                // ------------------------------------------------
                $response = $this->sendCommand(
                    $socket,
                    "RCPT TO:<{$email}>",
                    $conversation
                );

                $code = $this->getResponseCode($response);
                $result['smtp_response_code'] = $code;
                $result['smtp_response']      = $this->extractText($response);

                if ($code >= 200 && $code < 300) {
                    // 2xx = Definitely valid
                    $result['smtp_valid'] = true;

                } elseif ($code >= 400 && $code < 500) {
                    // 4xx = Temporary failure
                    if ($code === 421 || $code === 450 || $code === 451) {
                        // Greylist or temporary rejection - treat as unknown
                        $result['greylisted'] = true;
                        $result['smtp_valid'] = null; // unknown
                    } else {
                        $result['smtp_valid'] = false;
                    }

                } elseif ($code >= 500 && $code < 600) {
                    // 5xx = Permanent failure = invalid email
                    $result['smtp_valid'] = false;

                } else {
                    $result['smtp_valid'] = null; // Unknown
                }

                // ------------------------------------------------
                // QUIT gracefully
                // ------------------------------------------------
                $this->sendCommand($socket, 'QUIT', $conversation);
                break; // Got a response, no need to retry

            } catch (\Exception $e) {
                Log::warning("SMTP validation error for {$email}: " . $e->getMessage());

            } finally {
                if ($socket) {
                    @fclose($socket);
                }
            }
        }

        // Log conversation to database
        $this->logConversation($email, $mxHost, $mxIp, $conversation, $result);

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
        array  $result
    ): void {
        try {
            SmtpLog::create([
                'email'               => $email,
                'mx_host'             => $mxHost,
                'mx_ip'               => $mxIp,
                'port'                => self::SMTP_PORT,
                'conversation'        => implode("\n", $conversation),
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
