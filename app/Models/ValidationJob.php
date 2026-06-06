<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ValidationJob extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'uuid', 'name', 'filename', 'file_path', 'file_type',
        'status', 'total_emails', 'processed_emails', 'valid_emails',
        'invalid_emails', 'risky_emails', 'unknown_emails', 'disposable_count',
        'catch_all_count', 'credits_used', 'credits_refunded', 'progress',
        'error_message', 'download_token', 'download_expires_at',
        'processing_speed', 'estimated_seconds', 'started_at', 'completed_at',
        'settings', 'summary',
    ];

    protected $casts = [
        'started_at'          => 'datetime',
        'completed_at'        => 'datetime',
        'download_expires_at' => 'datetime',
        'settings'            => 'array',
        'summary'             => 'array',
        'progress'            => 'float',
        'total_emails'        => 'integer',
        'processed_emails'    => 'integer',
        'valid_emails'        => 'integer',
        'invalid_emails'      => 'integer',
        'risky_emails'        => 'integer',
        'unknown_emails'      => 'integer',
        'credits_used'        => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(fn ($job) => $job->uuid = $job->uuid ?? Str::uuid()->toString());
    }

    /**
     * Use uuid as the route key so route('user.bulk.show', $job) resolves by uuid.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ValidationResult::class, 'job_id');
    }

    // ============================================================
    // STATUS HELPERS
    // ============================================================

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isProcessing(): bool { return $this->status === 'processing'; }
    public function isCompleted(): bool  { return $this->status === 'completed'; }
    public function isFailed(): bool     { return $this->status === 'failed'; }
    public function isCancelled(): bool  { return $this->status === 'cancelled'; }

    public function updateProgress(int $processed, int $valid, int $invalid, int $risky, int $unknown): void
    {
        $progress = $this->total_emails > 0
            ? round(($processed / $this->total_emails) * 100, 2)
            : 0;

        $elapsed = now()->diffInSeconds($this->started_at);
        $speed   = $elapsed > 0 ? (int) ($processed / $elapsed) : 0;
        $remaining = $speed > 0 ? (int) (($this->total_emails - $processed) / $speed) : 0;

        $this->update([
            'processed_emails'  => $processed,
            'valid_emails'      => $valid,
            'invalid_emails'    => $invalid,
            'risky_emails'      => $risky,
            'unknown_emails'    => $unknown,
            'progress'          => min($progress, 100),
            'processing_speed'  => $speed,
            'estimated_seconds' => $remaining,
        ]);
    }

    public function markCompleted(): void
    {
        $this->update([
            'status'         => 'completed',
            'progress'       => 100,
            'completed_at'   => now(),
            'download_token' => Str::random(64),
            'download_expires_at' => now()->addDays(7),
        ]);
    }

    public function markFailed(string $error = ''): void
    {
        $this->update([
            'status'        => 'failed',
            'error_message' => $error,
            'completed_at'  => now(),
        ]);
    }

    // ============================================================
    // STATISTICS
    // ============================================================

    public function getValidRateAttribute(): float
    {
        if ($this->processed_emails === 0) return 0;
        return round(($this->valid_emails / $this->processed_emails) * 100, 1);
    }

    public function getInvalidRateAttribute(): float
    {
        if ($this->processed_emails === 0) return 0;
        return round(($this->invalid_emails / $this->processed_emails) * 100, 1);
    }

    public function generateSummary(): array
    {
        return [
            'total'       => $this->total_emails,
            'processed'   => $this->processed_emails,
            'valid'       => $this->valid_emails,
            'invalid'     => $this->invalid_emails,
            'risky'       => $this->risky_emails,
            'unknown'     => $this->unknown_emails,
            'disposable'  => $this->disposable_count,
            'catch_all'   => $this->catch_all_count,
            'valid_rate'  => $this->valid_rate,
            'credits_used'=> $this->credits_used,
            'duration'    => $this->started_at
                ? $this->completed_at?->diffInSeconds($this->started_at)
                : 0,
        ];
    }

    // ============================================================
    // COMPUTED ACCESSORS
    // ============================================================

    public function getProgressPercentageAttribute(): int
    {
        return (int) min(round($this->progress ?? 0), 100);
    }

    public function getEtaSecondsAttribute(): int
    {
        return $this->estimated_seconds ?? 0;
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopePending($query)    { return $query->where('status', 'pending'); }
    public function scopeProcessing($query) { return $query->where('status', 'processing'); }
    public function scopeCompleted($query)  { return $query->where('status', 'completed'); }
    public function scopeFailed($query)     { return $query->where('status', 'failed'); }
}
