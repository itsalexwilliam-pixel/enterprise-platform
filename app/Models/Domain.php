<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    protected $fillable = [
        'domain', 'mx_found', 'a_record_found', 'spf_found', 'dmarc_found',
        'is_disposable', 'is_free_email', 'is_catch_all', 'is_toxic', 'is_spam_trap',
        'reputation_score', 'mailbox_provider', 'validation_count', 'valid_rate',
        'mx_records', 'spf_record', 'dmarc_record', 'last_checked_at', 'cache_expires_at',
    ];

    protected $casts = [
        'mx_found'         => 'boolean',
        'a_record_found'   => 'boolean',
        'spf_found'        => 'boolean',
        'dmarc_found'      => 'boolean',
        'is_disposable'    => 'boolean',
        'is_free_email'    => 'boolean',
        'is_catch_all'     => 'boolean',
        'is_toxic'         => 'boolean',
        'is_spam_trap'     => 'boolean',
        'mx_records'       => 'array',
        'last_checked_at'  => 'datetime',
        'cache_expires_at' => 'datetime',
        'valid_rate'       => 'float',
        'reputation_score' => 'integer',
    ];

    public function isCacheValid(): bool
    {
        return $this->cache_expires_at && $this->cache_expires_at->isFuture();
    }

    public function scopeNeedsUpdate($q)
    {
        return $q->where(function ($query) {
            $query->whereNull('cache_expires_at')
                ->orWhere('cache_expires_at', '<', now());
        });
    }
}
