<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ValidationJob;
use App\Models\ValidationResult;
use App\Models\Transaction;
use App\Models\Worker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * ============================================================
 * Admin Dashboard Controller
 * Provides real-time system metrics and statistics
 * ============================================================
 */
class DashboardController extends Controller
{
    /**
     * GET /admin/dashboard
     * Main dashboard view
     */
    public function index()
    {
        $stats = $this->getStats();
        return view('admin.dashboard', compact('stats'));
    }

    /**
     * GET /api/v1/admin/dashboard
     * Real-time dashboard stats (refreshed every 30s)
     */
    public function stats(): JsonResponse
    {
        $stats = Cache::remember('admin_dashboard_stats', 30, fn () => $this->collectStats());

        return response()->json([
            'success' => true,
            'data'    => $stats,
        ]);
    }

    /**
     * Collect all dashboard statistics
     */
    private function collectStats(): array
    {
        $today     = now()->startOfDay();
        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        return [
            // ------------------------------------------------
            // USER STATISTICS
            // ------------------------------------------------
            'users' => [
                'total'          => User::count(),
                'active'         => User::where('status', 'active')->count(),
                'today'          => User::where('created_at', '>=', $today)->count(),
                'this_month'     => User::where('created_at', '>=', $thisMonth)->count(),
                'last_month'     => User::whereBetween('created_at', [$lastMonth, $thisMonth])->count(),
                'admins'         => User::where('role', 'admin')->count(),
                'resellers'      => User::where('role', 'reseller')->count(),
                'suspended'      => User::where('status', 'suspended')->count(),
            ],

            // ------------------------------------------------
            // CREDIT / REVENUE STATISTICS
            // ------------------------------------------------
            'revenue' => [
                'total_credits_sold'      => (int) Transaction::where('type', 'purchase')->sum('amount'),
                'revenue_total'           => (float) Transaction::where('type', 'purchase')->sum('price_paid'),
                'revenue_today'           => (float) Transaction::where('type', 'purchase')
                    ->where('created_at', '>=', $today)->sum('price_paid'),
                'revenue_this_month'      => (float) Transaction::where('type', 'purchase')
                    ->where('created_at', '>=', $thisMonth)->sum('price_paid'),
                'revenue_last_month'      => (float) Transaction::where('type', 'purchase')
                    ->whereBetween('created_at', [$lastMonth, $thisMonth])->sum('price_paid'),
                'total_credits_in_system' => (int) User::sum('credit_balance'),
            ],

            // ------------------------------------------------
            // VALIDATION STATISTICS
            // ------------------------------------------------
            'validations' => [
                'total'           => ValidationResult::count(),
                'today'           => ValidationResult::where('created_at', '>=', $today)->count(),
                'this_month'      => ValidationResult::where('created_at', '>=', $thisMonth)->count(),
                'valid_today'     => ValidationResult::where('created_at', '>=', $today)
                    ->where('status', 'valid')->count(),
                'invalid_today'   => ValidationResult::where('created_at', '>=', $today)
                    ->where('status', 'invalid')->count(),
                'avg_score_today' => (float) ValidationResult::where('created_at', '>=', $today)
                    ->avg('score'),
                'bulk_jobs_today' => ValidationJob::where('created_at', '>=', $today)->count(),
                'bulk_jobs_processing' => ValidationJob::where('status', 'processing')->count(),
                'api_validations_today'  => ValidationResult::where('created_at', '>=', $today)
                    ->whereNull('job_id')->count(),
            ],

            // ------------------------------------------------
            // SYSTEM HEALTH
            // ------------------------------------------------
            'system' => [
                'workers'         => $this->getWorkerStatus(),
                'queue_sizes'     => $this->getQueueSizes(),
                'redis_memory'    => $this->getRedisMemory(),
                'db_connections'  => $this->getDbConnections(),
                'server_time'     => now()->toISOString(),
            ],

            // ------------------------------------------------
            // HOURLY VALIDATION CHART (last 24h)
            // ------------------------------------------------
            'hourly_chart' => $this->getHourlyChart(),

            // ------------------------------------------------
            // STATUS DISTRIBUTION
            // ------------------------------------------------
            'status_distribution' => $this->getStatusDistribution($today),

            // ------------------------------------------------
            // TOP DOMAINS
            // ------------------------------------------------
            'top_domains' => $this->getTopDomains(),

            // ------------------------------------------------
            // RECENT ACTIVITY
            // ------------------------------------------------
            'recent_jobs' => $this->getRecentJobs(),
        ];
    }

    /**
     * Get stats (alias for collecting)
     */
    private function getStats(): array
    {
        return Cache::remember('admin_dashboard_stats', 30, fn () => $this->collectStats());
    }

    /**
     * Get worker status from database
     */
    private function getWorkerStatus(): array
    {
        $workers = Worker::all();

        return [
            'total'    => $workers->count(),
            'running'  => $workers->where('status', 'running')->count(),
            'idle'     => $workers->where('status', 'idle')->count(),
            'stopped'  => $workers->where('status', 'stopped')->count(),
            'crashed'  => $workers->where('status', 'crashed')->count(),
            'details'  => $workers->map(fn ($w) => [
                'id'             => $w->worker_id,
                'type'           => $w->type,
                'status'         => $w->status,
                'jobs_processed' => $w->jobs_processed,
                'jobs_failed'    => $w->jobs_failed,
                'last_heartbeat' => $w->last_heartbeat_at,
            ])->toArray(),
        ];
    }

    /**
     * Get RabbitMQ queue sizes
     */
    private function getQueueSizes(): array
    {
        try {
            $queues = ['smtp_validation', 'dns_validation', 'bulk_processing', 'webhooks'];
            $sizes  = [];

            foreach ($queues as $queue) {
                // Get queue size from Redis (Laravel Queue Driver stores info)
                $sizes[$queue] = (int) Redis::llen("queues:{$queue}");
            }

            return $sizes;

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get Redis memory usage
     */
    private function getRedisMemory(): array
    {
        try {
            $info = Redis::info('memory');
            return [
                'used_mb'   => round($info['used_memory'] / 1024 / 1024, 2),
                'peak_mb'   => round($info['used_memory_peak'] / 1024 / 1024, 2),
                'max_mb'    => round(($info['maxmemory'] ?? 0) / 1024 / 1024, 2),
            ];
        } catch (\Exception $e) {
            return ['used_mb' => 0, 'peak_mb' => 0, 'max_mb' => 0];
        }
    }

    /**
     * Get MySQL connection count
     */
    private function getDbConnections(): int
    {
        try {
            $result = DB::select("SHOW STATUS LIKE 'Threads_connected'");
            return (int) ($result[0]->Value ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get hourly validation chart data (last 24 hours)
     */
    private function getHourlyChart(): array
    {
        $hours = [];
        for ($i = 23; $i >= 0; $i--) {
            $hour = now()->subHours($i)->format('H:00');
            $start = now()->subHours($i)->startOfHour();
            $end   = now()->subHours($i)->endOfHour();

            $count = ValidationResult::whereBetween('created_at', [$start, $end])->count();
            $hours[] = [
                'hour'  => $hour,
                'count' => $count,
            ];
        }

        return $hours;
    }

    /**
     * Status distribution for today
     */
    private function getStatusDistribution(mixed $today): array
    {
        return ValidationResult::where('created_at', '>=', $today)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Top validated domains today
     */
    private function getTopDomains(): array
    {
        return ValidationResult::where('created_at', '>=', now()->startOfDay())
            ->select('domain', DB::raw('COUNT(*) as count'))
            ->groupBy('domain')
            ->orderByDesc('count')
            ->limit(10)
            ->pluck('count', 'domain')
            ->toArray();
    }

    /**
     * Recent bulk jobs
     */
    private function getRecentJobs(): array
    {
        return ValidationJob::with('user:id,name,email')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['id', 'uuid', 'user_id', 'name', 'status', 'total_emails', 'progress', 'created_at'])
            ->map(fn ($j) => [
                'id'           => $j->uuid,
                'name'         => $j->name,
                'user'         => $j->user?->name,
                'status'       => $j->status,
                'total_emails' => $j->total_emails,
                'progress'     => $j->progress,
                'created_at'   => $j->created_at,
            ])
            ->toArray();
    }

    /**
     * GET /admin/analytics
     * Revenue analytics
     */
    public function analytics(Request $request): JsonResponse
    {
        $period = $request->input('period', '30'); // days

        $revenueChart = DB::table('transactions')
            ->where('type', 'purchase')
            ->where('created_at', '>=', now()->subDays((int) $period))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(price_paid) as revenue'),
                DB::raw('COUNT(*) as transactions')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $validationChart = DB::table('validation_results')
            ->where('created_at', '>=', now()->subDays((int) $period))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "valid" THEN 1 ELSE 0 END) as valid'),
                DB::raw('SUM(CASE WHEN status = "invalid" THEN 1 ELSE 0 END) as invalid')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success'          => true,
            'revenue_chart'    => $revenueChart,
            'validation_chart' => $validationChart,
        ]);
    }
}
