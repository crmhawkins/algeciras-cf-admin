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
        // Fotos extraídas de algecirasclubdefutbol.com vía JS rendering. Filename = dorsal.
        $photo = fn (int $d) => file_exists(public_path("img/players/{$d}.png")) ? "img/players/{$d}.png" : null;

        $players = [
            // PORTEROS
            ['dorsal' => 1,  'display_name' => 'Iván Moreno',    'position' => 'portero',        'photo' => $photo(1)],
            ['dorsal' => 13, 'display_name' => 'Samu Casado',    'position' => 'portero',        'photo' => $photo(13)],

            // DEFENSAS
            ['dorsal' => 2,  'display_name' => 'Carlos Arauz',   'position' => 'defensa',        'photo' => $photo(2)],
            ['dorsal' => 3,  'display_name' => 'Joseca',         'position' => 'defensa',        'photo' => $photo(3)],
            ['dorsal' => 4,  'display_name' => 'Aleix Coch',     'position' => 'defensa',        'photo' => $photo(4)],
            ['dorsal' => 6,  'display_name' => 'Álvaro Mayorga', 'position' => 'defensa',        'photo' => $photo(6)],
            ['dorsal' => 11, 'display_name' => 'Tomás Sánchez',  'position' => 'defensa',        'photo' => $photo(11)],
            ['dorsal' => 15, 'display_name' => 'Víctor Ruiz',    'position' => 'defensa',        'photo' => $photo(15)],
            ['dorsal' => 16, 'display_name' => 'Ángel Gómez',    'position' => 'defensa',        'photo' => $photo(16)],
            ['dorsal' => 22, 'display_name' => 'París Adot',     'position' => 'defensa',        'photo' => $photo(22)],

            // CENTROCAMPISTAS y DELANTEROS — extra dorsales con foto pero sin nombre confirmado
            ['dorsal' => 5,  'display_name' => 'Centrocampista #5',  'position' => 'centrocampista', 'photo' => $photo(5)],
            ['dorsal' => 9,  'display_name' => 'Delantero #9',       'position' => 'delantero',      'photo' => $photo(9)],
            ['dorsal' => 10, 'display_name' => 'Centrocampista #10', 'position' => 'centrocampista', 'photo' => $photo(10)],
            ['dorsal' => 17, 'display_name' => 'Centrocampista #17', 'position' => 'centrocampista', 'photo' => $photo(17)],
            ['dorsal' => 20, 'display_name' => 'Delantero #20',      'position' => 'delantero',      'photo' => $photo(20)],
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
