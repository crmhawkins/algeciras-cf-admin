<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $cats = [
            ['slug' => 'abonos',          'name' => ['es' => 'Abonos',          'en' => 'Season passes'], 'color' => '#CF2E2E', 'sort_order' => 1],
            ['slug' => 'entradas',        'name' => ['es' => 'Entradas',        'en' => 'Tickets'],       'color' => '#0A0A0A', 'sort_order' => 2],
            ['slug' => 'equipacion',      'name' => ['es' => 'Equipación',      'en' => 'Kit'],           'color' => '#D4A24C', 'sort_order' => 3],
            ['slug' => 'lifestyle',       'name' => ['es' => 'Lifestyle',       'en' => 'Lifestyle'],     'color' => '#CF2E2E', 'sort_order' => 4],
            ['slug' => 'accesorios',      'name' => ['es' => 'Accesorios',      'en' => 'Accessories'],   'color' => '#3F3F3F', 'sort_order' => 5],
            ['slug' => 'edicion-117',     'name' => ['es' => 'Edición 117 Aniversario', 'en' => '117 Anniversary Edition'], 'color' => '#D4A24C', 'sort_order' => 6],
            ['slug' => 'cantera',         'name' => ['es' => 'Cantera',         'en' => 'Academy'],       'color' => '#1A6B2E', 'sort_order' => 7],
        ];

        foreach ($cats as $c) {
            Category::updateOrCreate(['slug' => $c['slug']], $c);
        }
    }
}
