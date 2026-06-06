<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Worker extends Model
{
    protected $fillable = [
        'worker_id', 'hostname', 'container_id', 'type', 'status',
        'jobs_processed', 'jobs_failed', 'current_job_id',
        'cpu_usage', 'memory_usage_mb', 'started_at', 'last_heartbeat_at',
    ];

    protected $casts = [
        'started_at'         => 'datetime',
        'last_heartbeat_at'  => 'datetime',
        'cpu_usage'          => 'float',
    ];

    public function isHealthy(): bool
    {
        if (! $this->last_heartbeat_at) return false;
        return $this->last_heartbeat_at->gt(now()->subMinutes(2));
    }

    public function scopeRunning($q) { return $q->where('status', 'running'); }
    public function scopeIdle($q)    { return $q->where('status', 'idle'); }
    public function scopeCrashed($q) { return $q->where('status', 'crashed'); }
}
