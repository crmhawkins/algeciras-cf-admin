<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Season;
use App\Models\Zone;
use Illuminate\Database\Seeder;

class ProductsSeeder extends Seeder
{
    /**
     * Productos sample basados en precios del pitch PPTX 2026-27:
     *  Fondo 90/115 — Preferencia 120/140 — Tribuna 190/225 — Palco 625/650 — Joven 65/75 — Pack Fam 350/400
     *  IVA: 10% en abonos/entradas, 21% en merch
     */
    public function run(): void
    {
        $season = Season::where('current', true)->first();
        if (! $season) return;

        $catAbonos    = Category::where('slug', 'abonos')->first();
        $catEntradas  = Category::where('slug', 'entradas')->first();
        $catEquip     = Category::where('slug', 'equipacion')->first();
        $catLifestyle = Category::where('slug', 'lifestyle')->first();
        $catAniv      = Category::where('slug', 'edicion-117')->first();

        $zFondo    = Zone::where('slug', 'fondo')->first();
        $zPref     = Zone::where('slug', 'preferencia')->first();
        $zTrib     = Zone::where('slug', 'tribuna')->first();
        $zPalco    = Zone::where('slug', 'palco-vip')->first();
        $zJoven    = Zone::where('slug', 'joven')->first();

        // ============ ABONOS 2026-27 ============
        $abonos = [
            ['sku' => 'ABO-FONDO-26-NEW',  'name' => 'Abono Fondo 2026-27 (Nuevo)',       'price' => 115, 'zone_id' => $zFondo?->id,  'capacity' => 1800],
            ['sku' => 'ABO-FONDO-26-REN',  'name' => 'Abono Fondo 2026-27 (Renovación)',  'price' => 90,  'zone_id' => $zFondo?->id,  'socios_only' => true],
            ['sku' => 'ABO-PREF-26-NEW',   'name' => 'Abono Preferencia 2026-27 (Nuevo)', 'price' => 140, 'zone_id' => $zPref?->id,   'capacity' => 1200],
            ['sku' => 'ABO-PREF-26-REN',   'name' => 'Abono Preferencia 2026-27 (Renov.)','price' => 120, 'zone_id' => $zPref?->id,   'socios_only' => true],
            ['sku' => 'ABO-TRIB-26-NEW',   'name' => 'Abono Tribuna 2026-27 (Nuevo)',     'price' => 225, 'zone_id' => $zTrib?->id,   'capacity' => 900],
            ['sku' => 'ABO-TRIB-26-REN',   'name' => 'Abono Tribuna 2026-27 (Renov.)',    'price' => 190, 'zone_id' => $zTrib?->id,   'socios_only' => true],
            ['sku' => 'ABO-PALCO-26-NEW',  'name' => 'Abono Palco VIP 2026-27 (Nuevo)',   'price' => 650, 'zone_id' => $zPalco?->id,  'capacity' => 50,  'featured' => true],
            ['sku' => 'ABO-JOV-26-NEW',    'name' => 'Abono Joven 15-25 (Nuevo)',         'price' => 75,  'zone_id' => $zJoven?->id,  'capacity' => 400, 'featured' => true],
        ];

        foreach ($abonos as $i => $a) {
            Product::updateOrCreate(
                ['sku' => $a['sku']],
                [
                    'type'        => Product::TYPE_ABONO,
                    'category_id' => $catAbonos?->id,
                    'name'        => ['es' => $a['name'], 'en' => str_replace(['Abono','Renov.','Renovación','Nuevo'], ['Season pass','Renew.','Renewal','New'], $a['name'])],
                    'description' => ['es' => 'Acceso a todos los partidos de liga 2026-27 en el Estadio Nuevo Mirador.', 'en' => 'Access to all 2026-27 league home games at Estadio Nuevo Mirador.'],
                    'price'       => $a['price'],
                    'vat_rate'    => 10,
                    'season_id'   => $season->id,
                    'zone_id'     => $a['zone_id'],
                    'capacity'    => $a['capacity'] ?? null,
                    'socios_only' => $a['socios_only'] ?? false,
                    'ship_required' => false,
                    'active'      => true,
                    'featured'    => $a['featured'] ?? false,
                    'sort_order'  => $i,
                ]
            );
        }

        // ============ MERCH SAMPLE ============
        $merch = [
            [
                'sku' => 'CAM-1A-26-27',
                'name' => ['es' => 'Camiseta 1ª Equipación 26-27', 'en' => 'Home Jersey 26-27'],
                'price' => 65.00, 'compare_at_price' => null,
                'category_id' => $catEquip?->id,
                'image' => 'img/sponsors/capelli.png',  // placeholder hasta tener foto real
                'featured' => true,
                'has_variants' => true,
                'stock' => null,
            ],
            [
                'sku' => 'CAM-2A-26-27',
                'name' => ['es' => 'Camiseta 2ª Equipación 26-27', 'en' => 'Away Jersey 26-27'],
                'price' => 65.00,
                'category_id' => $catEquip?->id,
                'has_variants' => true,
            ],
            [
                'sku' => 'CHA-PASEO-26',
                'name' => ['es' => 'Chándal Paseo 26-27', 'en' => 'Walkout Tracksuit 26-27'],
                'price' => 75.00,
                'category_id' => $catEquip?->id,
                'has_variants' => true,
            ],
            [
                'sku' => 'BUF-OFI',
                'name' => ['es' => 'Bufanda Oficial', 'en' => 'Official Scarf'],
                'price' => 12.00,
                'category_id' => $catLifestyle?->id,
                'featured' => true,
                'stock' => 500,
            ],
            [
                'sku' => 'GORRA-RJA',
                'name' => ['es' => 'Gorra Roja Algeciras', 'en' => 'Algeciras Red Cap'],
                'price' => 18.00,
                'category_id' => $catLifestyle?->id,
                'stock' => 300,
            ],
            [
                'sku' => 'PACK-117-ANI',
                'name' => ['es' => 'Pack 117 Aniversario', 'en' => '117 Anniversary Pack'],
                'price' => 49.00, 'compare_at_price' => 65.00,
                'category_id' => $catAniv?->id,
                'featured' => true,
                'stock' => 500,
            ],
        ];

        foreach ($merch as $i => $m) {
            Product::updateOrCreate(
                ['sku' => $m['sku']],
                array_merge([
                    'type'        => Product::TYPE_MERCH,
                    'vat_rate'    => 21,
                    'ship_required' => true,
                    'active'      => true,
                    'description' => ['es' => 'Producto oficial Algeciras CF.', 'en' => 'Official Algeciras CF merch.'],
                    'sort_order'  => 100 + $i,
                ], $m)
            );
        }
    }
}
