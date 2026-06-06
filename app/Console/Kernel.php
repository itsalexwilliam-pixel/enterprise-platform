<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * ============================================================
 * Laravel Console Kernel
 * Defines all scheduled tasks for the platform
 * ============================================================
 */
class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule
     */
    protected function schedule(Schedule $schedule): void
    {
        // --------------------------------------------------------
        // WORKER HEALTH MONITORING
        // Mark workers as stopped if no heartbeat for 2 minutes
        // --------------------------------------------------------
        $schedule->call(function () {
            \App\Models\Worker::where('last_heartbeat_at', '<', now()->subMinutes(2))
                ->where('status', 'running')
                ->update(['status' => 'stopped']);
        })->everyMinute()->name('worker-health-check')->withoutOverlapping();

        // --------------------------------------------------------
        // DOMAIN CACHE REFRESH
        // Refresh expired domain DNS caches in background
        // --------------------------------------------------------
        $schedule->call(function () {
            \App\Models\Domain::needsUpdate()->limit(500)->each(function ($domain) {
                dispatch(new \App\Jobs\RefreshDomainCache($domain->domain))
                    ->onQueue('dns_validation');
            });
        })->everyFiveMinutes()->name('domain-cache-refresh');

        // --------------------------------------------------------
        // API KEY DAILY RESET
        // Reset daily request counts at midnight
        // --------------------------------------------------------
        $schedule->call(function () {
            \Illuminate\Support\Facades\Redis::eval("
                local keys = redis.call('keys', 'api_daily:*:*')
                for _, key in ipairs(keys) do
                    local date = string.match(key, '%d%d%d%d%-%d%d%-%d%d$')
                    if date ~= os.date('%Y-%m-%d') then
                        redis.call('del', key)
                    end
                end
            ", 0);
        })->dailyAt('00:00')->name('api-key-daily-reset');

        // --------------------------------------------------------
        // FAILED WEBHOOK CLEANUP
        // Remove webhook delivery logs older than 30 days
        // --------------------------------------------------------
        $schedule->call(function () {
            \App\Models\WebhookDelivery::where('created_at', '<', now()->subDays(30))->delete();
        })->daily()->name('webhook-log-cleanup');

        // --------------------------------------------------------
        // SMTP LOG CLEANUP
        // Remove SMTP conversation logs older than 7 days
        // --------------------------------------------------------
        $schedule->call(function () {
            \App\Models\SmtpLog::where('created_at', '<', now()->subDays(7))->delete();
        })->daily()->name('smtp-log-cleanup');

        // --------------------------------------------------------
        // API REQUEST LOG CLEANUP (90 days retention)
        // --------------------------------------------------------
        $schedule->call(function () {
            \Illuminate\Support\Facades\DB::table('api_logs')
                ->where('created_at', '<', now()->subDays(90))
                ->delete();
        })->weekly()->name('api-log-cleanup');

        // --------------------------------------------------------
        // AUDIT LOG CLEANUP (1 year retention)
        // --------------------------------------------------------
        $schedule->call(function () {
            \App\Models\AuditLog::where('created_at', '<', now()->subYear())->delete();
        })->monthly()->name('audit-log-cleanup');

        // --------------------------------------------------------
        // EXPIRED DOWNLOAD TOKENS CLEANUP
        // --------------------------------------------------------
        $schedule->call(function () {
            \App\Models\ValidationJob::where('download_expires_at', '<', now())
                ->whereNotNull('download_token')
                ->update([
                    'download_token'     => null,
                    'download_expires_at'=> null,
                ]);
        })->hourly()->name('download-token-cleanup');

        // --------------------------------------------------------
        // SUBSCRIPTION RENEWAL CHECK
        // Check for expired subscriptions
        // --------------------------------------------------------
        $schedule->call(function () {
            \App\Models\Subscription::where('status', 'active')
                ->where('current_period_end', '<', now())
                ->each(function ($subscription) {
                    // Attempt renewal via Stripe
                    event(new \App\Events\SubscriptionExpired($subscription));
                });
        })->hourly()->name('subscription-renewal');

        // --------------------------------------------------------
        // DAILY USAGE REPORT (for admin email digest)
        // --------------------------------------------------------
        $schedule->call(function () {
            dispatch(new \App\Jobs\SendDailyReport())->onQueue('reports');
        })->dailyAt('08:00')->name('daily-usage-report')->timezone('UTC');

        // --------------------------------------------------------
        // DISPOSABLE DOMAIN LIST UPDATE
        // Fetch updates from external sources
        // --------------------------------------------------------
        $schedule->call(function () {
            dispatch(new \App\Jobs\UpdateDisposableDomainList())->onQueue('reports');
        })->weekly()->name('disposable-domains-update');

        // --------------------------------------------------------
        // PERFORMANCE METRICS AGGREGATION
        // Pre-compute hourly stats for dashboard charts
        // --------------------------------------------------------
        $schedule->call(function () {
            \Illuminate\Support\Facades\Cache::forget('admin_dashboard_stats');
        })->everyThirtySeconds()->name('metrics-refresh');
    }

    /**
     * Register the commands for the application
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
