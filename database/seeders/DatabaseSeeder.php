<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SeasonsSeeder::class,
            ZonesSeeder::class,
            SectorsSeeder::class,
            SeatsSeeder::class,
            CategoriesSeeder::class,
            PlayersSeeder::class,
            ClubStaffSeeder::class,
            SponsorsSeeder::class,
            SponsorsRealSeeder::class,
            NewsRealSeeder::class,
            ProductsSeeder::class,
            MerchSeeder::class,
            ProductVariantsSeeder::class,
            MatchesSeeder::class,
            // Demo socio: cupones + usuario de prueba con compras y tickets.
            CouponsSeeder::class,
            SocioDemoSeeder::class,
        ]);
    }
}
