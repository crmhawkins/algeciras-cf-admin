<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference','customer_id','guest_email','status','channel',
        'subtotal','vat','shipping_cost','total','currency',
        'payment_gateway','payment_intent_id',
        'shipping_address','billing_address',
        'tracking_carrier','tracking_number',
        'paid_at','fulfilled_at','cancelled_at','admin_notes',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'subtotal' => 'decimal:2',
        'vat' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function customer() { return $this->belongsTo(Customer::class); }
    public function items()    { return $this->hasMany(OrderItem::class); }
    public function tickets()  { return $this->hasManyThrough(Ticket::class, OrderItem::class); }

    public static function nextReference(): string
    {
        $year = now()->year;
        $count = static::where('reference', 'like', "ACF-{$year}-%")->count() + 1;
        return sprintf('ACF-%d-%06d', $year, $count);
    }

    public function scopePaid($q)    { return $q->where('status','paid'); }
    public function scopePending($q) { return $q->where('status','pending'); }
}
