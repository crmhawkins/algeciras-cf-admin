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
            CategoriesSeeder::class,
            PlayersSeeder::class,
            ClubStaffSeeder::class,
            SponsorsSeeder::class,
            ProductsSeeder::class,
            MerchSeeder::class,
            ProductVariantsSeeder::class,
            MatchesSeeder::class,
        ]);
    }
}
