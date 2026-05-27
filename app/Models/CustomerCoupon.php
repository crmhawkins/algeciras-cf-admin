<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerCoupon extends Model
{
    protected $fillable = ['customer_id', 'coupon_id', 'status', 'redeemed_at', 'redeemed_via'];
    protected $casts = ['redeemed_at' => 'datetime'];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function coupon()   { return $this->belongsTo(Coupon::class); }

    public function scopeAvailable($q) { return $q->where('status', 'available'); }
    public function scopeRedeemed($q)  { return $q->where('status', 'redeemed'); }

    public function redeem(string $via = 'web'): bool
    {
        if ($this->status !== 'available') return false;
        $this->update([
            'status'        => 'redeemed',
            'redeemed_at'   => now(),
            'redeemed_via'  => $via,
        ]);
        $this->coupon()->increment('used_count');
        return true;
    }
}
