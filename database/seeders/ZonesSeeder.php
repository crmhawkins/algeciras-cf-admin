<?php

namespace Database\Seeders;

use App\Models\Zone;
use Illuminate\Database\Seeder;

class ZonesSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            ['slug' => 'tribuna',     'name' => 'Tribuna',         'color' => '#CF2E2E', 'capacity_total' => 1200, 'sort_order' => 1],
            ['slug' => 'preferencia', 'name' => 'Preferencia',     'color' => '#D4A24C', 'capacity_total' => 1500, 'sort_order' => 2],
            ['slug' => 'fondo',       'name' => 'Fondo',           'color' => '#0A0A0A', 'capacity_total' => 2200, 'sort_order' => 3],
            ['slug' => 'joven',       'name' => 'Zona Joven',      'color' => '#E51339', 'capacity_total' => 400,  'sort_order' => 4],
            ['slug' => 'palco-vip',   'name' => 'Palco VIP',       'color' => '#D4A24C', 'capacity_total' => 60,   'sort_order' => 5],
        ];

        foreach ($zones as $z) {
            Zone::updateOrCreate(['slug' => $z['slug']], $z);
        }
    }
}
