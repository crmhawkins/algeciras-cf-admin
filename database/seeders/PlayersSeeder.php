<?php

namespace Database\Seeders;

use App\Models\Player;
use Illuminate\Database\Seeder;

/**
 * Plantilla 25/26 oficial del Algeciras CF.
 * Datos + fotos extraídos vía WordPress REST API de algecirasclubdefutbol.com
 * (filename patrón "Posicion-NoX-Nombre.jpg" en /wp-content/uploads/2025/01/).
 */
class PlayersSeeder extends Seeder
{
    public function run(): void
    {
        $players = [
            // PORTEROS
            [1,  'Iker Venteo',      'portero'],
            [13, 'Lucho García',     'portero'],

            // DEFENSAS
            [2,  'Rafa Roldán',      'defensa'],
            [3,  'Dani Merchán',     'defensa'],
            [4,  'Lautaro Spatz',    'defensa'],
            [5,  'Arnau Gaixas',     'defensa'],
            [11, 'Tomás Sánchez',    'defensa'],
            [20, 'París Adot',       'defensa'],
            [22, 'Aleix Coch',       'defensa'],
            [31, 'Curro',            'defensa'],

            // CENTROCAMPISTAS
            [6,  'Eric Montes',      'centrocampista'],
            [8,  'Iván Turrillo',    'centrocampista'],
            [14, 'Javi Alonso',      'centrocampista'],
            [15, 'Mario Fernández',  'centrocampista'],
            [19, 'Marino Illesca',   'centrocampista'],
            [21, 'Neco Celorio',     'centrocampista'],

            // DELANTEROS
            [7,  'Rodrigo Escudero', 'delantero'],
            [9,  'Manín',            'delantero'],
            [10, 'Diego Esteban',    'delantero'],
            [16, 'Javi Avilés',      'delantero'],
            [17, 'Álvaro Leiva',     'delantero'],
            [18, 'Juan Hernández',   'delantero'],
            [23, 'Javi Gómez',       'delantero'],
            [27, 'Daniel Recagno',   'delantero'],
        ];

        foreach ($players as $i => [$dorsal, $name, $position]) {
            // Slug del nombre para construir el path de la foto (mismo formato que el download)
            $slug = strtolower(strtr($name, [
                'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
                'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ñ' => 'n',
            ]));
            $slug = preg_replace('/\s+/', '-', $slug);
            $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
            $photoPath = "img/players/{$dorsal}-{$slug}.jpg";

            Player::updateOrCreate(
                ['dorsal' => $dorsal],
                [
                    'display_name' => $name,
                    'position'     => $position,
                    'photo'        => file_exists(public_path($photoPath)) ? $photoPath : null,
                    'nationality'  => 'España',
                    'active'       => true,
                    'sort_order'   => $i,
                ]
            );
        }
    }
}
