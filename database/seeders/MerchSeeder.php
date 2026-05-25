<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Productos de merchandising — datos reales scrapeados de latiendadelalgeciras.com
 * (Capelli Sport equipación + lifestyle).
 */
class MerchSeeder extends Seeder
{
    public function run(): void
    {
        $equip = Category::where('slug', 'equipacion')->first()?->id;
        $life  = Category::where('slug', 'lifestyle')->first()?->id;

        // SKUs viejos placeholder que vamos a reemplazar
        Product::merch()
            ->whereIn('sku', ['CAM-1A-26-27','CAM-2A-26-27','CHA-PASEO-26','BUF-OFI','GORRA-RJA','PACK-117-ANI'])
            ->delete();

        $items = [
            // [slug, nombre, precio, imagen, categoría]
            ['polo-capelli',            'Polo Capelli',                        39.00, 'Polo-algeciras-cf.jpg',                $equip],
            ['chaqueta-capelli',        'Chaqueta Capelli',                    95.00, 'Chaqueton-algeciras-cf.jpg',          $equip],
            ['camiseta-1a-equi',        'Camiseta 1ª Equipación Capelli',      39.00, 'Camiseta-algeciras-cf.jpg',           $equip],
            ['camiseta-2a-equi',        'Camiseta 2ª Equipación Capelli',      39.00, 'Segunda-camiseta-algeciras-cf.jpg',   $equip],
            ['sudadera-roja',           'Sudadera Roja',                       49.95, 'SUDADERA-ROJA-1.jpg',                 $equip],
            ['sudadera-negra',          'Sudadera Negra',                      49.95, 'Sudadera-Negra.jpg',                  $equip],
            ['pantalon-1a-equi',        'Pantalón Corto 1ª Equipación',        29.95, 'pantalon-1o-equi.jpg',                $equip],
            ['pantalon-chandal',        'Pantalón Largo Chándal',              29.95, 'Pantalon-chandal-negro.jpg',          $equip],
            ['bufanda-premium',         'Bufanda Premium',                     19.00, 'Bufanda-premium.jpg',                 $life],
            ['bufanda-franjas-blancas', 'Bufanda Franjas Blancas',             12.00, 'Bufanda-franjas-blancas-algeciras.jpg', $life],
            ['bufanda-bicolor',         'Bufanda Bicolor Diagonal',            12.00, 'Bufanda-bicolor-diagonal.jpg',        $life],
            ['gorra-blanca',            'Gorra Blanca Adulto',                 15.00, 'Gorra-blanca.jpg',                     $life],
            ['gorra-roja',              'Gorra Roja Adulto',                   15.00, 'Gorra-roja.jpg',                       $life],
            ['toalla-roja',             'Toalla Roja',                         14.95, 'Toalla-Algeciras.jpg',                 $life],
            ['botella-roja',            'Botella Roja Monocolor',              13.95, 'Botella-monocolor.jpg',                $life],
            ['zapatilla-hogar',         'Zapatilla Hogar',                     24.95, 'Zapatilla-hogar-Algeciras-cf.jpg',    $life],
        ];

        foreach ($items as $i => [$slug, $name, $price, $img, $cat]) {
            $needsSize = str_contains($name, 'Camiseta') || str_contains($name, 'Pant') || str_contains($name, 'Sudadera') || str_contains($name, 'Polo') || str_contains($name, 'Chaqueta');
            $path = "img/products/{$img}";
            $exists = file_exists(public_path($path));

            Product::updateOrCreate(
                ['sku' => 'MERCH-' . strtoupper($slug)],
                [
                    'type'          => Product::TYPE_MERCH,
                    'category_id'   => $cat,
                    'name'          => ['es' => $name, 'en' => $name],
                    'description'   => [
                        'es' => 'Producto oficial Algeciras CF. Marca técnica Capelli Sport.',
                        'en' => 'Official Algeciras CF merch by Capelli Sport.',
                    ],
                    'price'         => $price,
                    'vat_rate'      => 21,
                    'image'         => $exists ? $path : null,
                    'has_variants'  => $needsSize,
                    'ship_required' => true,
                    'active'        => true,
                    'featured'      => $i < 4,
                    'sort_order'    => 100 + $i,
                ]
            );
        }
    }
}
