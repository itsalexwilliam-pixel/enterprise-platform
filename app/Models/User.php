<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * User Model
 * Enterprise Email Validation Platform
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $status
 * @property string $role
 * @property int $credit_balance
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'company',
        'country',
        'timezone',
        'status',
        'role',
        'credit_balance',
        'team_id',
        'reseller_id',
        'settings',
        'white_label_domain',
        'stripe_customer_id',
        'two_factor_enabled',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'last_login_at',
        'last_login_ip',
        'email_verification_token',
        'email_verified_at',
        'password_reset_token',
        'password_reset_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'email_verification_token',
        'password_reset_token',
    ];

    protected $casts = [
        'email_verified_at'          => 'datetime',
        'last_login_at'              => 'datetime',
        'password_reset_expires_at'  => 'datetime',
        'two_factor_enabled'         => 'boolean',
        'two_factor_recovery_codes'  => 'array',
        'settings'                   => 'array',
        'credit_balance'             => 'integer',
        'password'                   => 'hashed',
    ];

    // ============================================================
    // RELATIONSHIPS
    // ============================================================

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function validationJobs(): HasMany
    {
        return $this->hasMany(ValidationJob::class);
    }

    public function validationResults(): HasMany
    {
        return $this->hasMany(ValidationResult::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latest();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reseller_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(User::class, 'reseller_id');
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    // ============================================================
    // CREDIT MANAGEMENT
    // ============================================================

    /**
     * Check if user has enough credits
     */
    public function hasCredits(int $amount): bool
    {
        return $this->credit_balance >= $amount;
    }

    /**
     * Add credits to user balance (with transaction log)
     */
    public function addCredits(
        int $amount,
        string $type,
        string $description = '',
        array $metadata = []
    ): Transaction {
        $balanceBefore = $this->credit_balance;
        $this->increment('credit_balance', $amount);
        $this->refresh();

        return Transaction::create([
            'user_id'        => $this->id,
            'reference'      => Transaction::generateReference(),
            'type'           => $type,
            'amount'         => $amount,
            'balance_before' => $balanceBefore,
            'balance_after'  => $this->credit_balance,
            'description'    => $description,
            'metadata'       => $metadata,
        ]);
    }

    /**
     * Deduct credits from user balance
     */
    public function deductCredits(
        int $amount,
        string $description = 'Email validation',
        ?int $jobId = null
    ): bool {
        if (! $this->hasCredits($amount)) {
            return false;
        }

        $balanceBefore = $this->credit_balance;
        $this->decrement('credit_balance', $amount);
        $this->refresh();

        Transaction::create([
            'user_id'              => $this->id,
            'reference'            => Transaction::generateReference(),
            'type'                 => 'deduction',
            'amount'               => -$amount,
            'balance_before'       => $balanceBefore,
            'balance_after'        => $this->credit_balance,
            'description'          => $description,
            'validation_job_id'    => $jobId,
        ]);

        return true;
    }

    // ============================================================
    // AUTHORIZATION HELPERS
    // ============================================================

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isReseller(): bool
    {
        return $this->role === 'reseller';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    // ============================================================
    // PLAN & SUBSCRIPTION HELPERS
    // ============================================================

    public function currentPlan(): ?Plan
    {
        return $this->subscription?->plan;
    }

    public function getApiRateLimit(): int
    {
        return $this->currentPlan()?->api_rate_limit ?? 60;
    }

    public function canUseSMTPValidation(): bool
    {
        return $this->currentPlan()?->smtp_validation ?? true;
    }

    public function canUseAIScoring(): bool
    {
        return $this->currentPlan()?->ai_scoring ?? false;
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeResellers($query)
    {
        return $query->where('role', 'reseller');
    }
}
