<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

/**
 * Variantes (tallas) para productos textiles con has_variants = true.
 * Tallas estándar XS-XXL con stock inicial 10 por talla.
 */
class ProductVariantsSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

        $textiles = Product::merch()
            ->where('has_variants', true)
            ->get();

        foreach ($textiles as $product) {
            foreach ($sizes as $i => $size) {
                ProductVariant::updateOrCreate(
                    ['product_id' => $product->id, 'size' => $size, 'color' => null],
                    [
                        'sku'        => "{$product->sku}-{$size}",
                        'stock'      => match ($size) {
                            'XS', 'XXL' => 5,
                            'S', 'XL'   => 10,
                            'M', 'L'    => 15,
                            default     => 10,
                        },
                        'active'     => true,
                        'sort_order' => $i,
                    ]
                );
            }
        }
    }
}
