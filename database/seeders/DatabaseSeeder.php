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
            CategoriesSeeder::class,
            PlayersSeeder::class,
            ClubStaffSeeder::class,
            SponsorsSeeder::class,
            ProductsSeeder::class,
            MatchesSeeder::class,
        ]);
    }
}
