<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidationResult extends Model
{
    protected $fillable = [
        'job_id', 'user_id', 'email', 'local_part', 'domain', 'status', 'score',
        'syntax_valid', 'syntax_error',
        'mx_found', 'mx_record', 'mx_priority', 'a_record_found',
        'spf_found', 'spf_record', 'dmarc_found', 'dmarc_record',
        'smtp_connectable', 'smtp_valid', 'smtp_banner', 'smtp_response',
        'smtp_response_code', 'catch_all', 'greylisted',
        'is_disposable', 'is_role_based', 'is_free_email', 'is_catch_all',
        'is_spam_trap', 'is_honeypot', 'is_toxic_domain', 'is_recently_active',
        'mailbox_provider', 'provider_type',
        'score_breakdown', 'validation_time_ms', 'validated_from_ip',
        'from_cache', 'cache_expires_at',
    ];

    protected $casts = [
        'syntax_valid'    => 'boolean',
        'mx_found'        => 'boolean',
        'a_record_found'  => 'boolean',
        'spf_found'       => 'boolean',
        'dmarc_found'     => 'boolean',
        'smtp_connectable'=> 'boolean',
        'smtp_valid'      => 'boolean',
        'catch_all'       => 'boolean',
        'greylisted'      => 'boolean',
        'is_disposable'   => 'boolean',
        'is_role_based'   => 'boolean',
        'is_free_email'   => 'boolean',
        'is_catch_all'    => 'boolean',
        'is_spam_trap'    => 'boolean',
        'is_honeypot'     => 'boolean',
        'is_toxic_domain' => 'boolean',
        'is_recently_active' => 'boolean',
        'from_cache'      => 'boolean',
        'score'           => 'integer',
        'score_breakdown' => 'array',
        'cache_expires_at'=> 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(ValidationJob::class, 'job_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get API response array
     */
    public function toApiResponse(): array
    {
        return [
            'email'             => $this->email,
            'status'            => $this->status,
            'score'             => $this->score,
            'mx_found'          => $this->mx_found,
            'smtp_check'        => $this->smtp_valid,
            'catch_all'         => $this->is_catch_all,
            'disposable'        => $this->is_disposable,
            'role_based'        => $this->is_role_based,
            'free_email'        => $this->is_free_email,
            'spam_trap'         => $this->is_spam_trap,
            'mailbox_provider'  => $this->mailbox_provider,
            'mx_record'         => $this->mx_record,
            'spf_record'        => $this->spf_found,
            'dmarc_record'      => $this->dmarc_found,
            'syntax_valid'      => $this->syntax_valid,
        ];
    }

    // Status helper methods
    public function isValid(): bool       { return $this->status === 'valid'; }
    public function isInvalid(): bool     { return $this->status === 'invalid'; }
    public function isRisky(): bool       { return $this->status === 'risky'; }
    public function isUnknown(): bool     { return $this->status === 'unknown'; }

    // Scopes
    public function scopeValid($query)    { return $query->where('status', 'valid'); }
    public function scopeInvalid($query)  { return $query->where('status', 'invalid'); }
    public function scopeRisky($query)    { return $query->where('status', 'risky'); }
    public function scopeDisposable($q)   { return $q->where('is_disposable', true); }
    public function scopeCatchAll($q)     { return $q->where('is_catch_all', true); }
}
