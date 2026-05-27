<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'title', 'description', 'type', 'value', 'image',
        'target_tier', 'max_uses_per_customer', 'total_stock',
        'used_count', 'valid_from', 'valid_until', 'active',
    ];

    protected $casts = [
        'value'       => 'decimal:2',
        'active'      => 'bool',
        'valid_from'  => 'date',
        'valid_until' => 'date',
    ];

    public function customerCoupons() { return $this->hasMany(CustomerCoupon::class); }

    public function scopeActive($q)   { return $q->where('active', true); }
    public function scopeForTier($q, string $tier)
    {
        return $q->whereIn('target_tier', ['all', $tier]);
    }

    public function isValid(): bool
    {
        if (! $this->active) return false;
        if ($this->valid_from && $this->valid_from->isFuture()) return false;
        if ($this->valid_until && $this->valid_until->isPast()) return false;
        if ($this->total_stock !== null && $this->used_count >= $this->total_stock) return false;
        return true;
    }

    public function getDisplayValueAttribute(): string
    {
        return match ($this->type) {
            'percent' => number_format($this->value, 0) . '%',
            'fixed'   => number_format($this->value, 2, ',', '.') . '€',
            'gift'    => '🎁',
        };
    }
}
