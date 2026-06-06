<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'key', 'key_prefix', 'status',
        'rate_limit_per_minute', 'rate_limit_per_day',
        'total_requests', 'requests_today', 'allowed_ips', 'permissions',
        'last_used_at', 'last_used_ip', 'expires_at',
    ];

    protected $casts = [
        'allowed_ips'  => 'array',
        'permissions'  => 'array',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    protected $hidden = ['key'];

    /**
     * Generate a new unique API key
     */
    public static function generateKey(): string
    {
        return 'ev_' . Str::random(56); // ev_ prefix + 56 random chars = 59 chars total
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getMaskedKeyAttribute(): string
    {
        return $this->key_prefix . str_repeat('*', 50);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
