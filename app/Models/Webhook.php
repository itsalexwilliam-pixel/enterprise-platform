<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Webhook extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'url', 'secret', 'events', 'status',
        'success_count', 'failure_count', 'last_triggered_at',
        'last_success_at', 'last_failure_at', 'last_error',
        'timeout_seconds', 'retry_count',
    ];

    protected $casts = [
        'events'            => 'array',
        'last_triggered_at' => 'datetime',
        'last_success_at'   => 'datetime',
        'last_failure_at'   => 'datetime',
    ];

    protected $hidden = ['secret'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function listensTo(string $event): bool
    {
        return in_array($event, $this->events ?? [], true) || in_array('*', $this->events ?? [], true);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
