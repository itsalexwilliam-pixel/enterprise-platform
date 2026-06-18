<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidateEmailRequest;
use App\Models\ApiKey;
use App\Models\ValidationJob;
use App\Services\Validation\EmailValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * ============================================================
 * Email Validation API Controller
 *
 * Endpoints:
 *   POST /api/v1/validate           - Single email validation
 *   POST /api/v1/validate/batch     - Batch validate (up to 100)
 *   GET  /api/v1/result/{id}        - Get validation result
 *   GET  /api/v1/jobs               - List bulk jobs
 *   GET  /api/v1/jobs/{uuid}        - Get bulk job status
 *   POST /api/v1/jobs/{uuid}/cancel - Cancel bulk job
 *   GET  /api/v1/jobs/{uuid}/download - Download results
 * ============================================================
 */
class EmailValidationController extends Controller
{
    public function __construct(
        private readonly EmailValidationService $validationService
    ) {}

    /**
     * POST /api/v1/validate
     *
     * Single email validation endpoint
     *
     * Request:
     * {
     *   "email": "test@example.com"
     * }
     *
     * Response:
     * {
     *   "email": "test@example.com",
     *   "status": "valid",
     *   "score": 98,
     *   "mx_found": true,
     *   "smtp_check": true,
     *   "catch_all": false,
     *   "disposable": false,
     *   ...
     * }
     */
    public function validateEmail(ValidateEmailRequest $request): JsonResponse
    {
        $startTime = microtime(true);
        $user      = $request->user();
        $email     = strtolower(trim($request->input('email')));
        $apiKey    = $request->attributes->get('api_key');

        // --------------------------------------------------------
        // Rate Limiting (per API key + per user)
        // --------------------------------------------------------
        $rateLimitKey = "api_rate:{$apiKey->id}";
        $rateLimit    = $user->getApiRateLimit();

        if (RateLimiter::tooManyAttempts($rateLimitKey, $rateLimit)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            return $this->errorResponse(
                'Rate limit exceeded. Please retry after ' . $seconds . ' seconds.',
                429,
                ['retry_after' => $seconds]
            );
        }

        RateLimiter::hit($rateLimitKey, 60); // 1 minute window

        // --------------------------------------------------------
        // Check User Credits
        // --------------------------------------------------------
        if (! $user->hasCredits(1)) {
            return $this->errorResponse(
                'Insufficient credits. Please purchase more credits to continue.',
                402
            );
        }

        // --------------------------------------------------------
        // Perform Validation
        // --------------------------------------------------------
        try {
            $options = [
                'smtp_validation' => $request->boolean('smtp_validation', true),
                'skip_cache'      => $request->boolean('skip_cache', false),
            ];

            $result = $this->validationService->validate($email, $user->id, $options);

            // Deduct credit (only if not from cache)
            if (! $result['from_cache']) {
                $user->deductCredits(1, "Email validation: {$email}");
            }

            // Save to database (ensure user_id is present — cache strips it)
            $result['user_id'] = $user->id;
            $savedResult = $this->validationService->saveResult($result);

            // Update API key stats
            $apiKey->increment('total_requests');
            $apiKey->update(['last_used_at' => now(), 'last_used_ip' => $request->ip()]);

            // Build response
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            return response()->json([
                'success'            => true,
                'email'              => $result['email'],
                'status'             => $result['status'],
                'score'              => $result['score'],
                'mx_found'           => $result['mx_found'],
                'smtp_check'         => $result['smtp_valid'],
                'catch_all'          => $result['is_catch_all'],
                'disposable'         => $result['is_disposable'],
                'role_based'         => $result['is_role_based'],
                'free_email'         => $result['is_free_email'],
                'spam_trap'          => $result['is_spam_trap'],
                'syntax_valid'       => $result['syntax_valid'],
                'mx_record'          => $result['mx_record'],
                'spf_record'         => $result['spf_found'],
                'dmarc_record'       => $result['dmarc_found'],
                'mailbox_provider'   => $result['mailbox_provider'],
                'did_you_mean'       => $result['did_you_mean'] ?? null,
                'risk_level'         => \App\Services\Validation\ScoringEngine::getRiskLevel($result['score']),
                'deliverability'     => \App\Services\Validation\ScoringEngine::getDeliverability($result['score']),
                'from_cache'         => $result['from_cache'],
                'validation_id'      => $savedResult->id,
                'credits_remaining'  => $user->fresh()->credit_balance,
                'response_time_ms'   => $responseTime,
            ]);

        } catch (\Exception $e) {
            Log::error("Validation API error: " . $e->getMessage(), [
                'email'   => $email,
                'user_id' => $user->id,
                'trace'   => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('Validation service temporarily unavailable.', 503);
        }
    }

    /**
     * POST /api/v1/validate/batch
     * Validate up to 100 emails in a single request
     */
    public function validateBatch(Request $request): JsonResponse
    {
        $request->validate([
            'emails'   => 'required|array|min:1|max:100',
            'emails.*' => 'required|email:filter',
        ]);

        $user   = $request->user();
        $emails = array_unique(array_map('strtolower', $request->input('emails')));
        $count  = count($emails);

        if (! $user->hasCredits($count)) {
            return $this->errorResponse(
                "Insufficient credits. You need {$count} credits but only have {$user->credit_balance}.",
                402
            );
        }

        $results = [];
        foreach ($emails as $email) {
            try {
                $result = $this->validationService->validate($email, $user->id);
                if (! $result['from_cache']) {
                    $user->deductCredits(1, "Batch validation: {$email}");
                }
                $this->validationService->saveResult($result);
                $results[] = [
                    'email'    => $email,
                    'status'   => $result['status'],
                    'score'    => $result['score'],
                    'mx_found' => $result['mx_found'],
                    'catch_all'=> $result['is_catch_all'],
                    'disposable'=> $result['is_disposable'],
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'email'  => $email,
                    'status' => 'error',
                    'error'  => 'Validation failed',
                ];
            }
        }

        return response()->json([
            'success'           => true,
            'total'             => $count,
            'results'           => $results,
            'credits_remaining' => $user->fresh()->credit_balance,
        ]);
    }

    /**
     * GET /api/v1/jobs
     * List user's bulk validation jobs
     */
    public function listJobs(Request $request): JsonResponse
    {
        $user = $request->user();
        $jobs = ValidationJob::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $jobs->through(fn ($job) => [
                'id'              => $job->uuid,
                'name'            => $job->name,
                'status'          => $job->status,
                'total_emails'    => $job->total_emails,
                'processed'       => $job->processed_emails,
                'valid'           => $job->valid_emails,
                'invalid'         => $job->invalid_emails,
                'progress'        => $job->progress,
                'credits_used'    => $job->credits_used,
                'created_at'      => $job->created_at,
                'completed_at'    => $job->completed_at,
            ]),
            'pagination' => [
                'current_page' => $jobs->currentPage(),
                'per_page'     => $jobs->perPage(),
                'total'        => $jobs->total(),
                'last_page'    => $jobs->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/jobs/{uuid}
     * Get bulk job status and progress
     */
    public function getJob(Request $request, string $uuid): JsonResponse
    {
        $job = ValidationJob::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'success'    => true,
            'job' => [
                'id'                => $job->uuid,
                'name'              => $job->name,
                'status'            => $job->status,
                'total_emails'      => $job->total_emails,
                'processed'         => $job->processed_emails,
                'valid'             => $job->valid_emails,
                'invalid'           => $job->invalid_emails,
                'risky'             => $job->risky_emails,
                'unknown'           => $job->unknown_emails,
                'disposable'        => $job->disposable_count,
                'catch_all'         => $job->catch_all_count,
                'progress'          => $job->progress,
                'processing_speed'  => $job->processing_speed,
                'estimated_seconds' => $job->estimated_seconds,
                'credits_used'      => $job->credits_used,
                'can_download'      => $job->isCompleted(),
                'download_url'      => $job->isCompleted()
                    ? route('api.jobs.download', ['uuid' => $job->uuid, 'token' => $job->download_token])
                    : null,
                'created_at'        => $job->created_at,
                'started_at'        => $job->started_at,
                'completed_at'      => $job->completed_at,
            ],
        ]);
    }

    /**
     * POST /api/v1/jobs/{uuid}/cancel
     * Cancel a running bulk job
     */
    public function cancelJob(Request $request, string $uuid): JsonResponse
    {
        $job = ValidationJob::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'processing'])
            ->firstOrFail();

        $job->update(['status' => 'cancelled', 'completed_at' => now()]);

        // Refund unused credits
        $refundCredits = $job->total_emails - $job->processed_emails;
        if ($refundCredits > 0) {
            $request->user()->addCredits($refundCredits, 'refund', "Refund for cancelled job: {$job->name}");
            $job->update(['credits_refunded' => $refundCredits]);
        }

        return response()->json([
            'success'          => true,
            'message'          => 'Job cancelled successfully.',
            'credits_refunded' => $refundCredits,
        ]);
    }

    /**
     * GET /api/v1/jobs/{uuid}/download?token=xxx
     * Download validation results (CSV/XLSX)
     */
    public function downloadJob(Request $request, string $uuid): mixed
    {
        $job = ValidationJob::where('uuid', $uuid)
            ->where('download_token', $request->query('token'))
            ->where('status', 'completed')
            ->where('download_expires_at', '>', now())
            ->firstOrFail();

        // Generate and stream CSV
        $filename = "validation_{$job->uuid}.csv";

        return response()->stream(function () use ($job) {
            $handle = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($handle, [
                'Email', 'Status', 'Score', 'MX Found', 'SMTP Valid',
                'Catch All', 'Disposable', 'Role Based', 'Free Email',
                'Spam Trap', 'Mailbox Provider', 'MX Record', 'SPF', 'DMARC'
            ]);

            // Stream results in chunks to avoid memory issues
            $job->results()->chunk(1000, function ($results) use ($handle) {
                foreach ($results as $result) {
                    fputcsv($handle, [
                        $result->email,
                        $result->status,
                        $result->score,
                        $result->mx_found ? 'Yes' : 'No',
                        $result->smtp_valid ? 'Yes' : 'No',
                        $result->is_catch_all ? 'Yes' : 'No',
                        $result->is_disposable ? 'Yes' : 'No',
                        $result->is_role_based ? 'Yes' : 'No',
                        $result->is_free_email ? 'Yes' : 'No',
                        $result->is_spam_trap ? 'Yes' : 'No',
                        $result->mailbox_provider ?? '',
                        $result->mx_record ?? '',
                        $result->spf_found ? 'Yes' : 'No',
                        $result->dmarc_found ? 'Yes' : 'No',
                    ]);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ============================================================
    // HELPERS
    // ============================================================

    private function errorResponse(string $message, int $code, array $extra = []): JsonResponse
    {
        return response()->json(array_merge([
            'success' => false,
            'error'   => $message,
        ], $extra), $code);
    }
}
