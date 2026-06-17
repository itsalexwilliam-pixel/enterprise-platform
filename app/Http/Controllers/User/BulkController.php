<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBulkValidation;
use App\Models\ValidationJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BulkController extends Controller
{
    public function index()
    {
        $jobs = ValidationJob::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('user.bulk.index', compact('jobs'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file'         => 'required|file|mimes:csv,txt,xlsx|max:102400',
            'name'         => 'nullable|string|max:100',
            'email_column' => 'nullable|string|max:50',
        ]);

        $user = auth()->user();

        if ($user->credit_balance <= 0) {
            return back()->withErrors(['file' => 'Insufficient credits. Please buy more.']);
        }

        $file    = $request->file('file');
        $ext     = strtolower($file->getClientOriginalExtension());
        $uuid    = Str::uuid()->toString();
        $path    = "uploads/{$user->id}/{$uuid}.{$ext}";

        // Resolve job name before storing the file (UploadedFile metadata is always available here)
        $originalName = $file->getClientOriginalName();
        $jobName      = ($request->filled('name') && is_string($request->input('name')))
            ? trim($request->input('name'))
            : $originalName;
        // Final safety net — should never be empty, but guard anyway
        if (empty($jobName)) {
            $jobName = $originalName ?: ('Job-' . now()->format('Ymd-His'));
        }

        Storage::disk('local')->putFileAs(dirname($path), $file, basename($path));

        $totalEmails = $this->countEmails(Storage::disk('local')->path($path), $ext);

        if ($totalEmails === 0) {
            Storage::disk('local')->delete($path);
            return back()->withErrors(['file' => 'No valid email addresses found.']);
        }

        if (! $user->hasCredits($totalEmails)) {
            Storage::disk('local')->delete($path);
            return back()->withErrors(['file' =>
                "Need {$totalEmails} credits, you have {$user->credit_balance}."
            ]);
        }

        $job = ValidationJob::create([
            'user_id'      => $user->id,
            'uuid'         => $uuid,
            'name'         => $jobName,
            'filename'     => $originalName,
            'file_path'    => $path,
            'file_type'    => $ext,
            'status'       => 'pending',
            'total_emails' => $totalEmails,
            'settings'     => ['email_column' => $request->input('email_column', 'email')],
        ]);

        $user->deductCredits($totalEmails, "Bulk job: {$job->name}", $job->id);
        $job->update(['credits_used' => $totalEmails]);

        ProcessBulkValidation::dispatch($job->id)->onQueue('bulk_processing');

        return redirect()->route('user.bulk.show', $job)
            ->with('success', 'File uploaded! Validation started.');
    }

    /**
     * Show a job by uuid (route model bound via getRouteKeyName = 'uuid').
     */
    public function show(ValidationJob $job)
    {
        // Ensure user owns this job
        abort_unless($job->user_id === auth()->id(), 403);

        $results = $job->results()->limit(50)->get();

        return view('user.bulk.show', compact('job', 'results'));
    }

    /**
     * JSON progress endpoint.
     */
    public function progress(ValidationJob $job)
    {
        abort_unless($job->user_id === auth()->id(), 403);

        return response()->json([
            'status'            => $job->status,
            'progress'          => $job->progress_percentage,
            'processed_emails'  => $job->processed_emails,
            'total_emails'      => $job->total_emails,
            'valid_emails'      => $job->valid_emails,
            'invalid_emails'    => $job->invalid_emails,
            'risky_emails'      => $job->risky_emails,
            'processing_speed'  => $job->processing_speed,
            'eta_seconds'       => $job->estimated_seconds,
            'download_token'    => $job->download_token,
        ]);
    }

    /**
     * Cancel a pending or processing job.
     */
    public function cancel(ValidationJob $job)
    {
        abort_unless($job->user_id === auth()->id(), 403);
        abort_unless(in_array($job->status, ['pending', 'processing']), 422);

        $job->update(['status' => 'cancelled', 'completed_at' => now()]);

        $refund = $job->total_emails - $job->processed_emails;
        if ($refund > 0) {
            auth()->user()->addCredits($refund, 'refund', "Cancelled job: {$job->name}");
            $job->update(['credits_refunded' => $refund]);
        }

        return redirect()->route('user.bulk.index')
            ->with('success', 'Job cancelled. Credits refunded.');
    }

    /**
     * Stream CSV download of results.
     */
    public function download(ValidationJob $job)
    {
        abort_unless($job->user_id === auth()->id(), 403);
        abort_unless($job->status === 'completed', 422);

        $filename = "validation_{$job->uuid}.csv";

        return response()->stream(function () use ($job) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Email', 'Status', 'Score', 'MX Found', 'SMTP Valid',
                'Catch All', 'Disposable', 'Role Based', 'Spam Trap',
                'Free Email', 'Provider', 'Risk Level', 'Deliverability',
            ]);

            $job->results()->chunk(500, function ($results) use ($handle) {
                foreach ($results as $r) {
                    fputcsv($handle, [
                        $r->email,
                        $r->status,
                        $r->score,
                        $r->mx_found      ? 'Yes' : 'No',
                        $r->smtp_valid    ? 'Yes' : 'No',
                        $r->is_catch_all  ? 'Yes' : 'No',
                        $r->is_disposable ? 'Yes' : 'No',
                        $r->is_role_based ? 'Yes' : 'No',
                        $r->is_spam_trap  ? 'Yes' : 'No',
                        $r->is_free_email ? 'Yes' : 'No',
                        $r->mailbox_provider ?? '',
                        \App\Services\Validation\ScoringEngine::getRiskLevel($r->score),
                        \App\Services\Validation\ScoringEngine::getDeliverability($r->score),
                    ]);
                }
            });
            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'X-Accel-Buffering'   => 'no',
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function countEmails(string $path, string $ext): int
    {
        $count  = 0;
        $handle = fopen($path, 'r');
        if (! $handle) return 0;

        if ($ext === 'csv') {
            $headers = null;
            while (($row = fgetcsv($handle, 1000)) !== false) {
                if (! $headers) { $headers = $row; continue; }
                $email = trim($row[0] ?? '');
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) $count++;
            }
        } else {
            // txt or plain text — one email per line
            while (($line = fgets($handle)) !== false) {
                if (filter_var(trim($line), FILTER_VALIDATE_EMAIL)) $count++;
            }
        }
        fclose($handle);
        return $count;
    }
}
