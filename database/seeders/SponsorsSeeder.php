<?php

namespace Database\Seeders;

use App\Models\Sponsor;
use Illuminate\Database\Seeder;

class SponsorsSeeder extends Seeder
{
    public function run(): void
    {
        $sponsors = [
            // Marca técnica (hasta 2030 según pitch)
            ['name' => 'Capelli Sport',          'slug' => 'capelli-sport',        'tier' => 'tecnico',     'logo' => 'img/sponsors/capelli.png',        'url' => 'https://capellisport.com',         'invert_on_dark' => false],

            // Principales
            ['name' => 'Hawkins',                'slug' => 'hawkins',              'tier' => 'principal',   'logo' => 'img/sponsors/hawkins.png',        'url' => 'https://hawkins.es',                'invert_on_dark' => true],
            ['name' => 'Quirónsalud',            'slug' => 'quironsalud',          'tier' => 'main',        'logo' => 'img/sponsors/quironsalud.svg',    'url' => 'https://www.quironsalud.com',       'invert_on_dark' => false],
            ['name' => 'Centro Gráfico',         'slug' => 'centro-grafico',       'tier' => 'main',        'logo' => 'img/sponsors/centro-grafico.png', 'url' => null,                                'invert_on_dark' => false],
            ['name' => 'EWYT',                   'slug' => 'ewyt',                 'tier' => 'main',        'logo' => 'img/sponsors/ewyt.png',           'url' => 'https://ewyt.es',                   'invert_on_dark' => true],

            // Secundarios (sin logos descargados todavía)
            ['name' => 'Obramat',                'slug' => 'obramat',              'tier' => 'secundario',  'logo' => null, 'url' => 'https://www.obramat.es'],
            ['name' => 'Eurograss',              'slug' => 'eurograss',            'tier' => 'secundario',  'logo' => null, 'url' => null],
            ['name' => 'Bezarala',               'slug' => 'bezarala',             'tier' => 'secundario',  'logo' => null, 'url' => null],
            ['name' => 'Omoda/Jaecoo',           'slug' => 'omoda-jaecoo',         'tier' => 'secundario',  'logo' => null, 'url' => null],
            ['name' => 'Gigantes Empresarios',   'slug' => 'gigantes-empresarios', 'tier' => 'colaborador', 'logo' => null, 'url' => null],
        ];

        foreach ($sponsors as $i => $s) {
            $s['sort_order'] = $i;
            $s['active'] = true;
            Sponsor::updateOrCreate(['slug' => $s['slug']], $s);
        }
    }
}
