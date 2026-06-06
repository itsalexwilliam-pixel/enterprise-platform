<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\User\DashboardController;
use App\Http\Controllers\User\BulkController;
use App\Http\Controllers\User\ApiKeyController;
use App\Http\Controllers\User\BillingController;
use App\Http\Controllers\User\WebhookController as UserWebhookController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\JobController as AdminJobController;
use App\Http\Controllers\Admin\TransactionController as AdminTransactionController;
use App\Http\Controllers\Admin\PlanController as AdminPlanController;
use App\Http\Controllers\Admin\SystemController as AdminSystemController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;

// ============================================================
// PUBLIC ROUTES
// ============================================================

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('user.dashboard');
    }
    return view('welcome');
})->name('home');

// Authentication (guest only)
Route::middleware('guest')->group(function () {
    Route::get('/register',             [RegisterController::class, 'showForm'])->name('register');
    Route::post('/register',            [RegisterController::class, 'register']);
    Route::get('/login',                [LoginController::class, 'showForm'])->name('login');
    Route::post('/login',               [LoginController::class, 'login']);
    Route::get('/forgot-password',      [LoginController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password',     [LoginController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [LoginController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password',      [LoginController::class, 'resetPassword'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Email Verification stub
Route::get('/verify-email', function () {
    return redirect()->route('user.dashboard')->with('info', 'Please verify your email.');
})->middleware('auth')->name('verification.notice');

// ============================================================
// AUTHENTICATED USER ROUTES (prefix: user.)
// ============================================================

Route::middleware(['auth'])->prefix('')->name('user.')->group(function () {

    // Dashboard
    Route::get('/dashboard',  [DashboardController::class, 'index'])->name('dashboard');

    // Single Validation page
    Route::get('/validate',   [DashboardController::class, 'validatePage'])->name('validate');

    // Bulk Validation
    Route::prefix('bulk')->name('bulk.')->group(function () {
        Route::get('/',                         [BulkController::class, 'index'])->name('index');
        Route::post('/upload',                  [BulkController::class, 'upload'])->name('upload');
        Route::get('/{job}',                    [BulkController::class, 'show'])->name('show');
        Route::patch('/{job}/cancel',           [BulkController::class, 'cancel'])->name('cancel');
        Route::get('/{job}/download',           [BulkController::class, 'download'])->name('download');
        Route::get('/{job}/progress',           [BulkController::class, 'progress'])->name('progress');
    });

    // API Keys
    Route::prefix('api-keys')->name('api-keys.')->group(function () {
        Route::get('/',        [ApiKeyController::class, 'index'])->name('index');
        Route::post('/',       [ApiKeyController::class, 'store'])->name('store');
        Route::put('/{id}',    [ApiKeyController::class, 'update'])->name('update');
        Route::delete('/{id}', [ApiKeyController::class, 'destroy'])->name('destroy');
    });

    // Billing
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/',          [BillingController::class, 'index'])->name('index');
        Route::post('/checkout', [BillingController::class, 'checkout'])->name('checkout');
    });
    // Shorthand alias used in some views
    Route::get('/billing',   [BillingController::class, 'index'])->name('billing');

    // Webhooks
    Route::prefix('webhooks')->name('webhooks.')->group(function () {
        Route::get('/',         [UserWebhookController::class, 'index'])->name('index');
        Route::post('/',        [UserWebhookController::class, 'store'])->name('store');
        Route::patch('/{id}',   [UserWebhookController::class, 'toggle'])->name('toggle');
        Route::delete('/{id}',  [UserWebhookController::class, 'destroy'])->name('destroy');
    });
    Route::get('/webhooks', [UserWebhookController::class, 'index'])->name('webhooks');

    // Account Settings
    Route::prefix('account')->name('account.')->group(function () {
        Route::get('/',               [DashboardController::class, 'account'])->name('index');
        Route::put('/',               [DashboardController::class, 'updateAccount'])->name('update');
        Route::put('/password',       [DashboardController::class, 'changePassword'])->name('password');
    });
    Route::get('/account', [DashboardController::class, 'account'])->name('account');
});

// ============================================================
// ADMIN ROUTES (prefix: admin.)
// ============================================================

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {

    // Dashboard
    Route::get('/',           [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard',  [AdminDashboardController::class, 'index']);

    // Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/',                  [AdminUserController::class, 'index'])->name('index');
        Route::get('/{user}',            [AdminUserController::class, 'show'])->name('show');
        Route::put('/{user}',            [AdminUserController::class, 'update'])->name('update');
        Route::post('/{user}/credits',   [AdminUserController::class, 'addCredits'])->name('add-credits');
        Route::delete('/{user}',         [AdminUserController::class, 'destroy'])->name('destroy');
    });

    // Bulk Jobs
    Route::prefix('jobs')->name('jobs.')->group(function () {
        Route::get('/',        [AdminJobController::class, 'index'])->name('index');
        Route::get('/{job}',   [AdminJobController::class, 'show'])->name('show');
        Route::delete('/{job}',[AdminJobController::class, 'destroy'])->name('destroy');
    });

    // Transactions
    Route::prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', [AdminTransactionController::class, 'index'])->name('index');
    });

    // Plans
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/',        [AdminPlanController::class, 'index'])->name('index');
        Route::put('/{plan}',  [AdminPlanController::class, 'update'])->name('update');
    });

    // System
    Route::get('/system',  [AdminSystemController::class, 'index'])->name('system');
    Route::get('/queues',  [AdminSystemController::class, 'index'])->name('queues');
    Route::get('/workers', [AdminSystemController::class, 'index'])->name('workers');

    // Audit Log (inline view — no dedicated controller needed)
    Route::get('/audit', function () {
        return view('admin.audit');
    })->name('audit');

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/',           [AdminSettingsController::class, 'index'])->name('index');
        Route::post('/clear-cache',[AdminSettingsController::class, 'clearCache'])->name('clear-cache');
        Route::post('/optimise',  [AdminSettingsController::class, 'optimise'])->name('optimise');
    });
    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('settings');

    // SMTP Servers (stub)
    Route::get('/smtp-servers', function () {
        return view('admin.smtp-servers');
    })->name('smtp-servers.index');

    // Domains (stub)
    Route::get('/domains', function () {
        return view('admin.domains');
    })->name('domains.index');
});
