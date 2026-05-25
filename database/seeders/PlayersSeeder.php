<?php

namespace Database\Seeders;

use App\Models\Player;
use Illuminate\Database\Seeder;

class PlayersSeeder extends Seeder
{
    /**
     * Plantilla parcial 2025-26 extraída de algecirasclubdefutbol.com/primer-equipo/plantilla
     * Faltan centrocampistas y delanteros (lazy-load JS, no se pudieron scrapear estáticamente).
     * Marcamos `active = true` para los confirmados; resto se rellena desde admin.
     */
    public function run(): void
    {
        $players = [
            // PORTEROS
            ['dorsal' => 1,  'display_name' => 'Iván Moreno',    'position' => 'portero'],
            ['dorsal' => 13, 'display_name' => 'Samu Casado',    'position' => 'portero'],

            // DEFENSAS
            ['dorsal' => 2,  'display_name' => 'Carlos Arauz',   'position' => 'defensa'],
            ['dorsal' => 3,  'display_name' => 'Joseca',         'position' => 'defensa'],
            ['dorsal' => 4,  'display_name' => 'Aleix Coch',     'position' => 'defensa'],
            ['dorsal' => 6,  'display_name' => 'Álvaro Mayorga', 'position' => 'defensa'],
            ['dorsal' => 11, 'display_name' => 'Tomás Sánchez',  'position' => 'defensa'],
            ['dorsal' => 15, 'display_name' => 'Víctor Ruiz',    'position' => 'defensa'],
            ['dorsal' => 16, 'display_name' => 'Ángel Gómez',    'position' => 'defensa'],
            ['dorsal' => 22, 'display_name' => 'París Adot',     'position' => 'defensa'],

            // CENTROCAMPISTAS + DELANTEROS — placeholders para completar desde admin
            ['dorsal' => null, 'display_name' => 'Centrocampista TBD',  'position' => 'centrocampista', 'active' => false],
            ['dorsal' => null, 'display_name' => 'Delantero TBD',       'position' => 'delantero',      'active' => false],
        ];

        foreach ($players as $i => $p) {
            $p['nationality'] = $p['nationality'] ?? 'España';
            $p['active']      = $p['active'] ?? true;
            $p['sort_order']  = $i;
            Player::updateOrCreate(
                ['display_name' => $p['display_name']],
                $p,
            );
        }
    }
}
