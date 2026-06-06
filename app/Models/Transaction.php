<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    protected $fillable = [
        'user_id', 'reference', 'type', 'amount', 'balance_before', 'balance_after',
        'price_paid', 'currency', 'stripe_payment_intent_id', 'stripe_invoice_id',
        'validation_job_id', 'description', 'metadata', 'ip_address',
    ];

    protected $casts = [
        'amount'         => 'integer',
        'balance_before' => 'integer',
        'balance_after'  => 'integer',
        'price_paid'     => 'float',
        'metadata'       => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function validationJob(): BelongsTo
    {
        return $this->belongsTo(ValidationJob::class, 'validation_job_id');
    }

    public static function generateReference(): string
    {
        return 'TXN-' . strtoupper(Str::random(16));
    }

    /**
     * Alias: credits is the same as amount (credit units, not monetary).
     */
    public function getCreditsAttribute(): int
    {
        return $this->amount;
    }

    public function isDebit(): bool
    {
        return $this->amount < 0;
    }

    public function isCredit(): bool
    {
        return $this->amount > 0;
    }

    public function scopeDebits($q)  { return $q->where('amount', '<', 0); }
    public function scopeCredits($q) { return $q->where('amount', '>', 0); }
    public function scopePurchases($q) { return $q->where('type', 'purchase'); }
    public function scopeDeductions($q) { return $q->where('type', 'deduction'); }
}
