<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class Cart
{
    public const SESSION_KEY = 'cart.items';

    /**
     * Estructura de cada item en sesión:
     *   key => [
     *      'product_id' => int,
     *      'variant_id' => int|null,
     *      'qty'        => int,
     *   ]
     */
    public function items(): Collection
    {
        return collect(Session::get(self::SESSION_KEY, []))
            ->map(function (array $row, string $key) {
                $product = Product::with('variants','category')->find($row['product_id']);
                if (! $product) return null;
                $variant = $row['variant_id'] ? ProductVariant::find($row['variant_id']) : null;
                $unitPrice = (float) ($variant?->price_override ?? $product->price);
                return (object) [
                    'key'         => $key,
                    'product'     => $product,
                    'variant'     => $variant,
                    'qty'         => (int) $row['qty'],
                    'unit_price'  => $unitPrice,
                    'subtotal'    => round($unitPrice * (int) $row['qty'] / (1 + $product->vat_rate / 100), 2),
                    'vat_amount'  => round($unitPrice * (int) $row['qty'] - ($unitPrice * (int) $row['qty'] / (1 + $product->vat_rate / 100)), 2),
                    'total'       => round($unitPrice * (int) $row['qty'], 2),
                ];
            })
            ->filter()
            ->values();
    }

    public function add(int $productId, ?int $variantId = null, int $qty = 1): string
    {
        $key = $this->makeKey($productId, $variantId);
        $items = Session::get(self::SESSION_KEY, []);

        if (isset($items[$key])) {
            $items[$key]['qty'] += $qty;
        } else {
            $items[$key] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'qty'        => max(1, $qty),
            ];
        }
        Session::put(self::SESSION_KEY, $items);
        return $key;
    }

    public function update(string $key, int $qty): void
    {
        $items = Session::get(self::SESSION_KEY, []);
        if (! isset($items[$key])) return;
        if ($qty <= 0) {
            unset($items[$key]);
        } else {
            $items[$key]['qty'] = $qty;
        }
        Session::put(self::SESSION_KEY, $items);
    }

    public function remove(string $key): void
    {
        $items = Session::get(self::SESSION_KEY, []);
        unset($items[$key]);
        Session::put(self::SESSION_KEY, $items);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        return (int) collect(Session::get(self::SESSION_KEY, []))->sum('qty');
    }

    public function subtotal(): float
    {
        return round($this->items()->sum('subtotal'), 2);
    }

    public function vat(): float
    {
        return round($this->items()->sum('vat_amount'), 2);
    }

    public function total(): float
    {
        return round($this->items()->sum('total'), 2);
    }

    private function makeKey(int $productId, ?int $variantId): string
    {
        return $variantId ? "{$productId}-{$variantId}" : (string) $productId;
    }
}
