<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBulkValidation;
use App\Models\ValidationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * ============================================================
 * Bulk Upload Controller
 * Handles CSV/XLSX/TXT file uploads for bulk validation
 *
 * Supports up to 10 million emails per job
 * Uses chunked reading for memory efficiency
 * ============================================================
 */
class BulkUploadController extends Controller
{
    private const MAX_FILE_SIZE  = 102400; // 100MB in KB
    private const ALLOWED_TYPES  = ['csv', 'txt', 'xlsx'];
    private const MAX_EMAILS     = 10000000; // 10 million

    /**
     * POST /api/v1/bulk/upload
     * Upload email list file for validation
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file'  => 'required|file|mimes:csv,txt,xlsx|max:' . self::MAX_FILE_SIZE,
            'name'  => 'nullable|string|max:100',
            'email_column' => 'nullable|string|max:50', // for CSV with headers
        ]);

        $user = $request->user();
        $file = $request->file('file');

        // --------------------------------------------------------
        // Check user credit balance before processing
        // --------------------------------------------------------
        if ($user->credit_balance <= 0) {
            return $this->error('You need credits to process a bulk validation job.', 402);
        }

        // --------------------------------------------------------
        // Store file securely
        // --------------------------------------------------------
        $fileExtension = strtolower($file->getClientOriginalExtension());
        $storagePath   = "uploads/{$user->id}/" . Str::uuid() . ".{$fileExtension}";

        Storage::disk('local')->putFileAs(
            dirname($storagePath),
            $file,
            basename($storagePath)
        );

        // --------------------------------------------------------
        // Count emails in file
        // --------------------------------------------------------
        $totalEmails = $this->countEmails(
            Storage::disk('local')->path($storagePath),
            $fileExtension,
            $request->input('email_column', 'email')
        );

        if ($totalEmails === 0) {
            Storage::disk('local')->delete($storagePath);
            return $this->error('No valid email addresses found in the uploaded file.');
        }

        if ($totalEmails > self::MAX_EMAILS) {
            Storage::disk('local')->delete($storagePath);
            return $this->error("File contains too many emails. Maximum is " . number_format(self::MAX_EMAILS) . ".");
        }

        // --------------------------------------------------------
        // Verify user has enough credits
        // --------------------------------------------------------
        if (! $user->hasCredits($totalEmails)) {
            Storage::disk('local')->delete($storagePath);
            return $this->error(
                "Insufficient credits. You need {$totalEmails} credits but only have {$user->credit_balance}.",
                402
            );
        }

        // --------------------------------------------------------
        // Create validation job record
        // --------------------------------------------------------
        $job = ValidationJob::create([
            'user_id'       => $user->id,
            'uuid'          => Str::uuid()->toString(),
            'name'          => $request->input('name', $file->getClientOriginalName()),
            'filename'      => $file->getClientOriginalName(),
            'file_path'     => $storagePath,
            'file_type'     => $fileExtension,
            'status'        => 'pending',
            'total_emails'  => $totalEmails,
            'settings'      => [
                'email_column'    => $request->input('email_column', 'email'),
                'smtp_validation' => $request->boolean('smtp_validation', true),
                'skip_duplicates' => $request->boolean('skip_duplicates', true),
            ],
        ]);

        // --------------------------------------------------------
        // Deduct credits upfront (refund on cancel/partial failure)
        // --------------------------------------------------------
        $user->deductCredits(
            $totalEmails,
            "Bulk validation: {$job->name}",
            $job->id
        );

        $job->update(['credits_used' => $totalEmails]);

        // --------------------------------------------------------
        // Dispatch to RabbitMQ queue
        // --------------------------------------------------------
        ProcessBulkValidation::dispatch($job->id)
            ->onQueue(config('queue.queues.bulk', 'bulk_processing'))
            ->delay(now()->addSeconds(2)); // Brief delay for DB consistency

        return response()->json([
            'success'       => true,
            'message'       => 'File uploaded successfully. Processing has started.',
            'job_id'        => $job->uuid,
            'total_emails'  => $totalEmails,
            'status'        => 'pending',
            'status_url'    => route('api.jobs.status', $job->uuid),
            'credits_used'  => $totalEmails,
            'credits_remaining' => $user->fresh()->credit_balance,
        ], 201);
    }

    /**
     * Count emails in uploaded file
     * Handles CSV, TXT, XLSX formats efficiently
     */
    private function countEmails(string $filePath, string $type, string $emailColumn = 'email'): int
    {
        return match ($type) {
            'csv'  => $this->countCsvEmails($filePath, $emailColumn),
            'txt'  => $this->countTxtEmails($filePath),
            'xlsx' => $this->countXlsxEmails($filePath, $emailColumn),
            default => 0,
        };
    }

    /**
     * Count emails in CSV file (streams file, memory efficient)
     */
    private function countCsvEmails(string $filePath, string $emailColumn): int
    {
        $count    = 0;
        $handle   = fopen($filePath, 'r');
        $headers  = null;
        $emailIdx = 0;

        if (! $handle) return 0;

        while (($row = fgetcsv($handle, 1000, ',')) !== false) {
            if ($headers === null) {
                $headers  = array_map('strtolower', $row);
                $emailIdx = array_search($emailColumn, $headers);
                if ($emailIdx === false) {
                    // Try to find first email-looking column
                    $emailIdx = 0;
                }
                continue;
            }

            $email = trim($row[$emailIdx] ?? '');
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $count++;
            }
        }

        fclose($handle);
        return $count;
    }

    /**
     * Count emails in TXT file (one per line)
     */
    private function countTxtEmails(string $filePath): int
    {
        $count  = 0;
        $handle = fopen($filePath, 'r');

        if (! $handle) return 0;

        while (($line = fgets($handle)) !== false) {
            $email = trim($line);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $count++;
            }
        }

        fclose($handle);
        return $count;
    }

    /**
     * Count emails in XLSX file
     */
    private function countXlsxEmails(string $filePath, string $emailColumn): int
    {
        // Uses PhpSpreadsheet for XLSX reading
        try {
            $reader      = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $sheet       = $spreadsheet->getActiveSheet();
            $count       = 0;

            $headers  = null;
            $emailIdx = 1;

            foreach ($sheet->getRowIterator() as $row) {
                $cells = [];
                foreach ($row->getCellIterator() as $cell) {
                    $cells[] = $cell->getValue();
                }

                if ($headers === null) {
                    $headers  = array_map('strtolower', $cells);
                    $idx      = array_search($emailColumn, $headers);
                    $emailIdx = $idx !== false ? $idx : 0;
                    continue;
                }

                $email = trim($cells[$emailIdx] ?? '');
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $count++;
                }
            }

            return $count;
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function error(string $message, int $code = 422): JsonResponse
    {
        return response()->json(['success' => false, 'error' => $message], $code);
    }
}
