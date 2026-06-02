<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'title', 'description', 'type', 'value', 'image',
        'target_tier', 'max_uses_per_customer', 'total_stock',
        'used_count', 'valid_from', 'valid_until', 'active',
        // Checkout extension (2026-06-03)
        'applies_to', 'min_order_total', 'max_discount',
        'customer_required', 'public_code',
    ];

    protected $casts = [
        'value'             => 'decimal:2',
        'active'            => 'bool',
        'valid_from'        => 'date',
        'valid_until'       => 'date',
        'customer_required' => 'bool',
        'public_code'       => 'bool',
        'min_order_total'   => 'decimal:2',
        'max_discount'      => 'decimal:2',
    ];

    public function customerCoupons() { return $this->hasMany(CustomerCoupon::class); }
    public function orders()          { return $this->hasMany(Order::class); }

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

    /**
     * Comprueba si el cupón aplica a un pedido y calcula el descuento.
     *
     * @param  float  $orderTotal  Subtotal del pedido sobre el que aplicar.
     * @param  string $productType 'all'|'abono'|'entrada'|'merch' (singular según se reciba).
     * @return array{applies: bool, discount: float, reason: ?string}
     */
    public function applyTo(float $orderTotal, string $productType = 'all'): array
    {
        if (! $this->isValid()) {
            return ['applies' => false, 'discount' => 0.0, 'reason' => 'Cupón caducado o no disponible'];
        }

        if (! $this->matchesProductType($productType)) {
            return ['applies' => false, 'discount' => 0.0, 'reason' => 'Este cupón no aplica a estos productos'];
        }

        $min = $this->min_order_total !== null ? (float) $this->min_order_total : null;
        if ($min !== null && $orderTotal < $min) {
            return [
                'applies'  => false,
                'discount' => 0.0,
                'reason'   => 'Mínimo ' . number_format($min, 2, ',', '.') . '€ para aplicar este cupón',
            ];
        }

        $discount = match ($this->type) {
            'percent' => round($orderTotal * ((float) $this->value) / 100, 2),
            'fixed'   => round((float) $this->value, 2),
            'gift'    => 0.0,
            default   => 0.0,
        };

        if ($this->max_discount !== null) {
            $cap = (float) $this->max_discount;
            if ($discount > $cap) {
                $discount = $cap;
            }
        }

        if ($discount > $orderTotal) {
            $discount = $orderTotal;
        }

        $discount = round(max(0.0, $discount), 2);

        return ['applies' => true, 'discount' => $discount, 'reason' => null];
    }

    /**
     * applies_to vs productType del pedido.
     *
     * applies_to values:
     *   - 'all'              → cualquier producto
     *   - 'abonos'           → sólo cuando productType in ['abono','abonos','all']
     *   - 'entradas'         → sólo cuando productType in ['entrada','entradas','all']
     *   - 'abonos_entradas'  → ['abono','entrada','abonos','entradas','all']
     *   - 'merch'            → ['merch','merchandising','all']
     */
    protected function matchesProductType(string $productType): bool
    {
        $appliesTo = $this->applies_to ?? 'all';
        if ($appliesTo === 'all') return true;

        $pt = strtolower(trim($productType));

        return match ($appliesTo) {
            'abonos'          => in_array($pt, ['abono', 'abonos', 'all'], true),
            'entradas'        => in_array($pt, ['entrada', 'entradas', 'all'], true),
            'abonos_entradas' => in_array($pt, ['abono', 'abonos', 'entrada', 'entradas', 'all'], true),
            'merch'           => in_array($pt, ['merch', 'merchandising', 'all'], true),
            default           => true,
        };
    }
}
