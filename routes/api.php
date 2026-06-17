<?php

/**
 * ============================================================
 * API Routes - Enterprise Email Validation Platform
 *
 * Base URL: /api/v1/
 * Authentication: API Key via header X-API-Key or Bearer token
 *
 * Rate Limits are enforced per API key
 * ============================================================
 */

use App\Http\Controllers\Api\EmailValidationController;
use App\Http\Controllers\Api\BulkUploadController;
use App\Http\Controllers\Auth\ApiAuthController;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\AccountController;
use Illuminate\Support\Facades\Route;

// ============================================================
// PUBLIC ROUTES (no authentication required)
// ============================================================

Route::prefix('v1')->group(function () {

    // Health Check
    Route::get('/health', fn () => response()->json([
        'status'    => 'healthy',
        'version'   => '1.0.0',
        'timestamp' => now()->toISOString(),
    ]));

    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('/register',        [ApiAuthController::class, 'register']);
        Route::post('/login',           [ApiAuthController::class, 'login']);
        Route::post('/forgot-password', [ApiAuthController::class, 'forgotPassword']);
        Route::post('/reset-password',  [ApiAuthController::class, 'resetPassword']);
        Route::post('/verify-email',    [ApiAuthController::class, 'verifyEmail']);
    });

    // Stripe Webhooks (no auth - uses HMAC signature verification)
    Route::post('/webhooks/stripe', [App\Http\Controllers\Api\StripeWebhookController::class, 'handle']);
});

// ============================================================
// AUTHENTICATED ROUTES (requires API Key or Sanctum token)
// ============================================================

Route::prefix('v1')
    ->middleware(['auth:sanctum', 'api.key', 'user.active'])
    ->group(function () {

    // --------------------------------------------------------
    // EMAIL VALIDATION
    // --------------------------------------------------------
    Route::post('/validate',         [EmailValidationController::class, 'validateEmail'])
        ->name('api.validate');

    Route::post('/validate/batch',   [EmailValidationController::class, 'validateBatch'])
        ->name('api.validate.batch');

    Route::get('/result/{id}',       [EmailValidationController::class, 'getResult'])
        ->name('api.result');

    // --------------------------------------------------------
    // BULK VALIDATION JOBS
    // --------------------------------------------------------
    Route::prefix('bulk')->group(function () {
        Route::post('/upload',          [BulkUploadController::class, 'upload'])
            ->name('api.bulk.upload');

        Route::get('/jobs',             [EmailValidationController::class, 'listJobs'])
            ->name('api.jobs.list');

        Route::get('/jobs/{uuid}',      [EmailValidationController::class, 'getJob'])
            ->name('api.jobs.status');

        Route::post('/jobs/{uuid}/cancel', [EmailValidationController::class, 'cancelJob'])
            ->name('api.jobs.cancel');

        Route::get('/jobs/{uuid}/download', [EmailValidationController::class, 'downloadJob'])
            ->name('api.jobs.download');
    });

    // --------------------------------------------------------
    // ACCOUNT MANAGEMENT
    // --------------------------------------------------------
    Route::prefix('account')->group(function () {
        Route::get('/',                 [AccountController::class, 'show'])
            ->name('api.account');

        Route::put('/',                 [AccountController::class, 'update'])
            ->name('api.account.update');

        Route::post('/change-password', [AccountController::class, 'changePassword']);

        // API Keys
        Route::get('/api-keys',         [AccountController::class, 'listApiKeys']);
        Route::post('/api-keys',        [AccountController::class, 'createApiKey']);
        Route::put('/api-keys/{id}',    [AccountController::class, 'updateApiKey']);
        Route::delete('/api-keys/{id}', [AccountController::class, 'revokeApiKey']);

        // Credits & Billing
        Route::get('/credits',          [AccountController::class, 'creditBalance']);
        Route::get('/transactions',     [AccountController::class, 'transactions']);

        // Usage Statistics
        Route::get('/usage',            [AccountController::class, 'usage']);
        Route::get('/usage/daily',      [AccountController::class, 'dailyUsage']);
    });

    // --------------------------------------------------------
    // WEBHOOKS
    // --------------------------------------------------------
    Route::prefix('webhooks')->group(function () {
        Route::get('/',             [WebhookController::class, 'index']);
        Route::post('/',            [WebhookController::class, 'store']);
        Route::put('/{id}',         [WebhookController::class, 'update']);
        Route::delete('/{id}',      [WebhookController::class, 'destroy']);
        Route::post('/{id}/test',   [WebhookController::class, 'test']);
        Route::get('/{id}/logs',    [WebhookController::class, 'logs']);
    });

    // Logout
    Route::post('/auth/logout', [ApiAuthController::class, 'logout']);
});

// ============================================================
// ADMIN ROUTES (admin role only)
// ============================================================

Route::prefix('v1/admin')
    ->middleware(['auth:sanctum', 'role:admin'])
    ->group(function () {

    // Dashboard stats
    Route::get('/dashboard',    [App\Http\Controllers\Admin\DashboardController::class, 'stats']);

    // User management
    Route::get('/users',        [App\Http\Controllers\Admin\UserController::class, 'index']);
    Route::get('/users/{id}',   [App\Http\Controllers\Admin\UserController::class, 'show']);
    Route::put('/users/{id}',   [App\Http\Controllers\Admin\UserController::class, 'update']);
    Route::post('/users/{id}/suspend',  [App\Http\Controllers\Admin\UserController::class, 'suspend']);
    Route::post('/users/{id}/credits',  [App\Http\Controllers\Admin\UserController::class, 'adjustCredits']);

    // Plans
    Route::apiResource('/plans', App\Http\Controllers\Admin\PlanController::class);

    // Domain blacklist management
    Route::get('/domains',              [App\Http\Controllers\Admin\DomainController::class, 'index']);
    Route::post('/domains/disposable',  [App\Http\Controllers\Admin\DomainController::class, 'addDisposable']);
    Route::post('/domains/spam-trap',   [App\Http\Controllers\Admin\DomainController::class, 'addSpamTrap']);
    Route::delete('/domains/{id}',      [App\Http\Controllers\Admin\DomainController::class, 'destroy']);

    // System monitoring
    Route::get('/workers',      [App\Http\Controllers\Admin\SystemController::class, 'workers']);
    Route::get('/queues',       [App\Http\Controllers\Admin\SystemController::class, 'queues']);
    Route::get('/metrics',      [App\Http\Controllers\Admin\SystemController::class, 'metrics']);

    // SMTP Servers
    Route::apiResource('/smtp-servers', App\Http\Controllers\Admin\SmtpServerController::class);

    // Audit Logs
    Route::get('/audit-logs',   [App\Http\Controllers\Admin\AuditController::class, 'index']);
});
