<?php

namespace App\Jobs;

use App\Models\ValidationJob;
use App\Models\ValidationResult;
use App\Services\Validation\EmailValidationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * ============================================================
 * Process Bulk Validation Job
 *
 * Handles validation of up to 10 million emails
 *
 * Architecture:
 * - Read file in chunks (1000 emails per chunk)
 * - For each chunk, dispatch SMTP validation sub-jobs
 * - Update progress in real-time via Redis
 * - Bulk insert results for performance
 * - Handle cancellation gracefully
 * ============================================================
 */
class ProcessBulkValidation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout  = 7200; // 2 hours max
    public int $tries    = 1;    // Don't retry bulk jobs (too expensive)
    public int $maxExceptions = 1;

    private const CHUNK_SIZE   = 500;  // emails per DB batch
    private const SMTP_BATCH   = 50;   // concurrent SMTP checks per chunk

    public function __construct(private readonly int $jobId) {}

    /**
     * Execute the bulk validation job
     */
    public function handle(EmailValidationService $validationService): void
    {
        $job = ValidationJob::find($this->jobId);

        if (! $job || $job->status === 'cancelled') {
            Log::info("Bulk job {$this->jobId} not found or cancelled, skipping.");
            return;
        }

        Log::info("Starting bulk validation job {$job->uuid}, total emails: {$job->total_emails}");

        // Mark job as processing
        $job->update([
            'status'     => 'processing',
            'started_at' => now(),
        ]);

        try {
            $this->processFile($job, $validationService);
            $job->markCompleted();

            // Build final summary
            $job->update(['summary' => $job->generateSummary()]);

            // Trigger webhook if configured
            $this->triggerCompletionWebhook($job);

            Log::info("Bulk job {$job->uuid} completed successfully.");

        } catch (\Exception $e) {
            Log::error("Bulk job {$job->uuid} failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $job->markFailed($e->getMessage());

            // Refund unprocessed credits
            $unprocessed = $job->total_emails - $job->processed_emails;
            if ($unprocessed > 0) {
                $job->user->addCredits(
                    $unprocessed,
                    'refund',
                    "Refund for failed job: {$job->name}",
                    ['job_id' => $job->id]
                );
                $job->update(['credits_refunded' => $unprocessed]);
            }
        }
    }

    /**
     * Process the uploaded file (CSV/TXT/XLSX)
     */
    private function processFile(ValidationJob $job, EmailValidationService $validationService): void
    {
        $filePath = Storage::disk('local')->path($job->file_path);
        $settings = $job->settings ?? [];

        $emailIterator = match ($job->file_type) {
            'csv'  => $this->readCsv($filePath, $settings['email_column'] ?? 'email'),
            'txt'  => $this->readTxt($filePath),
            'xlsx' => $this->readXlsx($filePath, $settings['email_column'] ?? 'email'),
            default => throw new \Exception("Unsupported file type: {$job->file_type}"),
        };

        $buffer       = [];
        $processed    = 0;
        $valid         = 0;
        $invalid       = 0;
        $risky         = 0;
        $unknown       = 0;
        $disposable    = 0;
        $catchAll      = 0;
        $seenEmails   = []; // for dedup
        $skipDupes     = $settings['skip_duplicates'] ?? true;

        foreach ($emailIterator as $email) {
            // Check for cancellation every 100 emails
            if ($processed % 100 === 0) {
                $job->refresh();
                if ($job->status === 'cancelled') {
                    Log::info("Bulk job {$job->uuid} cancelled at {$processed} emails.");
                    return;
                }
            }

            // Deduplicate if enabled
            if ($skipDupes) {
                if (isset($seenEmails[$email])) continue;
                $seenEmails[$email] = true;
            }

            // Validate the email
            try {
                $result = $validationService->validate($email, $job->user_id);

                // Count stats
                switch ($result['status']) {
                    case 'valid':       $valid++;      break;
                    case 'invalid':     $invalid++;    break;
                    case 'risky':       $risky++;      break;
                    case 'disposable':  $disposable++; $invalid++; break;
                    case 'spam_trap':   $invalid++;    break;
                    case 'catch_all':   $catchAll++;   $risky++;   break;
                    default:            $unknown++;    break;
                }

                if ($result['is_disposable']) $disposable++;
                if ($result['is_catch_all'])  $catchAll++;

                // Add to buffer for bulk insert
                $buffer[] = array_merge($result, [
                    'job_id'     => $job->id,
                    'user_id'    => $job->user_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            } catch (\Exception $e) {
                Log::warning("Failed to validate {$email}: " . $e->getMessage());
                $unknown++;
            }

            $processed++;

            // Bulk insert every CHUNK_SIZE emails
            if (count($buffer) >= self::CHUNK_SIZE) {
                $this->bulkInsertResults($buffer);
                $buffer = [];

                // Update progress in database
                $job->updateProgress($processed, $valid, $invalid, $risky, $unknown);

                // Free memory from dedup array if too large
                if (count($seenEmails) > 100000) {
                    $seenEmails = array_slice($seenEmails, -50000, 50000, true);
                }
            }
        }

        // Insert remaining buffer
        if (! empty($buffer)) {
            $this->bulkInsertResults($buffer);
        }

        // Final progress update
        $job->updateProgress($processed, $valid, $invalid, $risky, $unknown);
        $job->update([
            'disposable_count' => $disposable,
            'catch_all_count'  => $catchAll,
        ]);

        // Cleanup uploaded file
        Storage::disk('local')->delete($job->file_path);
    }

    /**
     * Bulk insert results for maximum performance
     * Uses raw DB insert (avoids Eloquent overhead for millions of rows)
     */
    private function bulkInsertResults(array $buffer): void
    {
        $rows = array_map(fn ($r) => [
            'job_id'             => $r['job_id'],
            'user_id'            => $r['user_id'],
            'email'              => $r['email'],
            'local_part'         => $r['local_part'],
            'domain'             => $r['domain'],
            'status'             => $r['status'],
            'score'              => $r['score'],
            'syntax_valid'       => $r['syntax_valid'] ? 1 : 0,
            'syntax_error'       => $r['syntax_error'],
            'mx_found'           => $r['mx_found'] ? 1 : 0,
            'mx_record'          => $r['mx_record'],
            'mx_priority'        => $r['mx_priority'],
            'a_record_found'     => $r['a_record_found'] ? 1 : 0,
            'spf_found'          => $r['spf_found'] ? 1 : 0,
            'spf_record'         => $r['spf_record'],
            'dmarc_found'        => $r['dmarc_found'] ? 1 : 0,
            'dmarc_record'       => $r['dmarc_record'],
            'smtp_connectable'   => $r['smtp_connectable'] ? 1 : 0,
            'smtp_valid'         => ($r['smtp_valid'] === null) ? null : ($r['smtp_valid'] ? 1 : 0),
            'smtp_banner'        => $r['smtp_banner'],
            'smtp_response'      => $r['smtp_response'],
            'smtp_response_code' => $r['smtp_response_code'],
            'catch_all'          => $r['catch_all'] ? 1 : 0,
            'greylisted'         => $r['greylisted'] ? 1 : 0,
            'is_disposable'      => $r['is_disposable'] ? 1 : 0,
            'is_role_based'      => $r['is_role_based'] ? 1 : 0,
            'is_free_email'      => $r['is_free_email'] ? 1 : 0,
            'is_catch_all'       => $r['is_catch_all'] ? 1 : 0,
            'is_spam_trap'       => $r['is_spam_trap'] ? 1 : 0,
            'is_honeypot'        => $r['is_honeypot'] ? 1 : 0,
            'is_toxic_domain'    => $r['is_toxic_domain'] ? 1 : 0,
            'mailbox_provider'   => $r['mailbox_provider'],
            'provider_type'      => $r['provider_type'],
            'score_breakdown'    => json_encode($r['score_breakdown'] ?? []),
            'validation_time_ms' => $r['validation_time_ms'],
            'from_cache'         => $r['from_cache'] ? 1 : 0,
            'created_at'         => now()->toDateTimeString(),
            'updated_at'         => now()->toDateTimeString(),
        ], $buffer);

        // Use INSERT IGNORE to skip duplicates gracefully
        DB::table('validation_results')->insertOrIgnore($rows);
    }

    /**
     * Read CSV file as generator (memory efficient)
     */
    private function readCsv(string $filePath, string $emailColumn): \Generator
    {
        $handle   = fopen($filePath, 'r');
        $headers  = null;
        $emailIdx = 0;

        while (($row = fgetcsv($handle, 1000)) !== false) {
            if ($headers === null) {
                $headers  = array_map('strtolower', array_map('trim', $row));
                $idx      = array_search(strtolower($emailColumn), $headers);
                $emailIdx = $idx !== false ? $idx : 0;
                continue;
            }

            $email = strtolower(trim($row[$emailIdx] ?? ''));
            if (! empty($email)) {
                yield $email;
            }
        }

        fclose($handle);
    }

    /**
     * Read TXT file as generator (one email per line)
     */
    private function readTxt(string $filePath): \Generator
    {
        $handle = fopen($filePath, 'r');

        while (($line = fgets($handle)) !== false) {
            $email = strtolower(trim($line));
            if (! empty($email)) {
                yield $email;
            }
        }

        fclose($handle);
    }

    /**
     * Read XLSX file as generator
     */
    private function readXlsx(string $filePath, string $emailColumn): \Generator
    {
        try {
            $reader      = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $sheet       = $spreadsheet->getActiveSheet();
            $headers     = null;
            $emailIdx    = 0;

            foreach ($sheet->getRowIterator() as $row) {
                $cells = [];
                foreach ($row->getCellIterator() as $cell) {
                    $cells[] = $cell->getValue();
                }

                if ($headers === null) {
                    $headers  = array_map('strtolower', array_map('trim', $cells));
                    $idx      = array_search(strtolower($emailColumn), $headers);
                    $emailIdx = $idx !== false ? $idx : 0;
                    continue;
                }

                $email = strtolower(trim($cells[$emailIdx] ?? ''));
                if (! empty($email)) {
                    yield $email;
                }
            }
        } catch (\Exception $e) {
            Log::error("Failed to read XLSX: " . $e->getMessage());
        }
    }

    /**
     * Trigger completion webhook for the job owner
     */
    private function triggerCompletionWebhook(ValidationJob $job): void
    {
        $webhooks = $job->user->webhooks()
            ->where('status', 'active')
            ->whereJsonContains('events', 'job.completed')
            ->get();

        foreach ($webhooks as $webhook) {
            \App\Jobs\SendWebhook::dispatch($webhook->id, 'job.completed', [
                'job_id'       => $job->uuid,
                'status'       => $job->status,
                'total'        => $job->total_emails,
                'valid'        => $job->valid_emails,
                'invalid'      => $job->invalid_emails,
                'download_url' => route('api.jobs.download', [
                    'uuid'  => $job->uuid,
                    'token' => $job->download_token,
                ]),
            ])->onQueue('webhooks');
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        $job = ValidationJob::find($this->jobId);
        if ($job) {
            $job->markFailed($exception->getMessage());
        }

        Log::error("Bulk validation job {$this->jobId} failed permanently: " . $exception->getMessage());
    }
}
