<?php

namespace Database\Seeders;

use App\Models\FootballMatch;
use App\Models\Season;
use Illuminate\Database\Seeder;

class MatchesSeeder extends Seeder
{
    public function run(): void
    {
        $season = Season::where('current', true)->first();
        if (! $season) return;

        // Hito conocido del pitch: J1 = 29 ago 2026
        FootballMatch::updateOrCreate(
            ['season_id' => $season->id, 'matchday' => 1, 'opponent' => 'Rival por confirmar (J1)'],
            [
                'competition' => 'Primera RFEF',
                'venue'       => 'home',
                'stadium'     => 'Nuevo Mirador',
                'kickoff_at'  => '2026-08-29 18:00:00',
                'status'      => 'scheduled',
                'notes'       => ['from' => 'plan_digital_PPTX'],
            ]
        );

        // Trofeo Patrona — 15 ago 2026 (también del pitch)
        FootballMatch::updateOrCreate(
            ['season_id' => $season->id, 'matchday' => null, 'opponent' => 'Trofeo Virgen de la Patrona'],
            [
                'competition' => 'Trofeo Patrona',
                'venue'       => 'home',
                'stadium'     => 'Nuevo Mirador',
                'kickoff_at'  => '2026-08-15 20:00:00',
                'status'      => 'scheduled',
            ]
        );
    }
}
