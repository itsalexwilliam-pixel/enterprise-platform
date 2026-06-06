<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditPackage extends Model
{
    protected $fillable = [
        'name', 'credits', 'price', 'bonus_credits',
        'is_popular', 'is_active', 'sort_order', 'stripe_price_id',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'is_popular'    => 'boolean',
        'price'         => 'decimal:2',
        'credits'       => 'integer',
        'bonus_credits' => 'integer',
        'sort_order'    => 'integer',
    ];

    /**
     * Total credits including bonus.
     */
    public function getTotalCreditsAttribute(): int
    {
        return $this->credits + ($this->bonus_credits ?? 0);
    }

    /**
     * Alias for is_popular (views use $pkg->popular).
     */
    public function getPopularAttribute(): bool
    {
        return (bool) $this->is_popular;
    }

    public function getPricePerCreditAttribute(): float
    {
        if ($this->credits <= 0) return 0;
        return round($this->price / $this->credits, 6);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
