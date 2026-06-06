<?php

namespace App\Console\Commands;

use App\Models\Worker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * ============================================================
 * Worker Heartbeat Command
 * Run by each worker to report status to the database
 * Called automatically by the queue worker lifecycle
 * ============================================================
 */
class WorkerHeartbeat extends Command
{
    protected $signature   = 'worker:heartbeat {--type=smtp} {--id=1}';
    protected $description = 'Send worker heartbeat to update status in database';

    public function handle(): int
    {
        $workerId   = env('WORKER_ID', $this->option('type') . '_' . $this->option('id'));
        $workerType = env('WORKER_TYPE', $this->option('type'));

        Worker::updateOrCreate(
            ['worker_id' => $workerId],
            [
                'hostname'           => gethostname(),
                'container_id'       => env('HOSTNAME', gethostname()),
                'type'               => $workerType,
                'status'             => 'running',
                'last_heartbeat_at'  => now(),
                'cpu_usage'          => sys_getloadavg()[0] * 100 / 4, // Rough estimate
                'memory_usage_mb'    => (int) (memory_get_usage(true) / 1024 / 1024),
            ]
        );

        return Command::SUCCESS;
    }
}
