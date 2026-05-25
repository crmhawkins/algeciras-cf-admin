<?php

namespace Database\Seeders;

use App\Models\Season;
use Illuminate\Database\Seeder;

class SeasonsSeeder extends Seeder
{
    public function run(): void
    {
        Season::updateOrCreate(['name' => '2025-26'], [
            'start_at' => '2025-08-15',
            'end_at'   => '2026-06-30',
            'current'  => false,
        ]);

        Season::updateOrCreate(['name' => '2026-27'], [
            'start_at' => '2026-08-29',
            'end_at'   => '2027-06-30',
            'current'  => true,
        ]);
    }
}
