<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference','customer_id','guest_email','status','channel',
        'subtotal','vat','shipping_cost','gestion_fee','total','currency',
        'payment_gateway','payment_intent_id',
        'shipping_address','billing_address',
        'tracking_carrier','tracking_number',
        'paid_at','fulfilled_at','cancelled_at','admin_notes',
        // Cupón aplicado (2026-06-03)
        'coupon_id','coupon_code','discount_amount',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'subtotal' => 'decimal:2',
        'vat' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'gestion_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /** Constante % comisión gastos de gestión (5% sobre subtotal). */
    public const GESTION_FEE_RATE = 0.05;

    /**
     * Calcula los gastos de gestión sobre un subtotal dado.
     * Redondea a 2 decimales a la baja (el cliente nunca paga > 5% real).
     */
    public static function calcGestionFee(float $subtotal): float
    {
        return floor($subtotal * self::GESTION_FEE_RATE * 100) / 100;
    }

    public function customer() { return $this->belongsTo(Customer::class); }
    public function items()    { return $this->hasMany(OrderItem::class); }
    public function tickets()  { return $this->hasManyThrough(Ticket::class, OrderItem::class); }
    public function coupon()   { return $this->belongsTo(Coupon::class); }

    public static function nextReference(): string
    {
        $year = now()->year;
        $count = static::where('reference', 'like', "ACF-{$year}-%")->count() + 1;
        return sprintf('ACF-%d-%06d', $year, $count);
    }

    public function scopePaid($q)    { return $q->where('status','paid'); }
    public function scopePending($q) { return $q->where('status','pending'); }
}
