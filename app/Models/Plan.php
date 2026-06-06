<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'type', 'billing_cycle', 'price',
        'credits_included', 'price_per_credit', 'api_rate_limit', 'bulk_limit',
        'smtp_validation', 'ai_scoring', 'webhook_support', 'white_label',
        'reseller_access', 'priority_support', 'max_team_members', 'max_api_keys',
        'stripe_price_id', 'features', 'is_active', 'is_visible', 'sort_order',
    ];

    protected $casts = [
        'smtp_validation'  => 'boolean',
        'ai_scoring'       => 'boolean',
        'webhook_support'  => 'boolean',
        'white_label'      => 'boolean',
        'reseller_access'  => 'boolean',
        'priority_support' => 'boolean',
        'is_active'        => 'boolean',
        'is_visible'       => 'boolean',
        'features'         => 'array',
        'price'            => 'float',
        'price_per_credit' => 'float',
        'credits_included' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function isFree(): bool { return $this->price == 0; }

    // ============================================================
    // ACCESSOR ALIASES (views use these friendly names)
    // ============================================================

    /** Views & forms use $plan->monthly_credits — maps to DB column credits_included */
    public function getMonthlyCreditsAttribute(): int
    {
        return (int) $this->credits_included;
    }

    /** Views & forms use $plan->price_monthly — maps to DB column price */
    public function getPriceMonthlyAttribute(): float
    {
        return (float) $this->price;
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeActive($q)  { return $q->where('is_active', true); }
    public function scopeVisible($q) { return $q->where('is_visible', true); }
    public function scopeOrdered($q) { return $q->orderBy('sort_order'); }
}
