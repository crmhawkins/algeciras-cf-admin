<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

/**
 * Sectores del Estadio Nuevo Mirador — mapping extraído del SVG real
 * de compralaentrada.com (data-region → nombre + precio + capacidad).
 *
 * Formato: [svg_region, name, zone, parity, number, price_adult, price_youth, capacity, available]
 * - precio adulto / juvenil de la temporada 2025/26
 * - capacity = plazas libres en el momento del scrape (mayo 2026)
 * - available = false para palcos, accesos y otros no-vendibles
 */
class SectorsSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // SVG_REG | NAME                    | ZONE          | PARITY | NUM   | ADULT  | YOUTH  | CAP | AVAILABLE
            [830, 'Tribuna Alta Par 1',           'tribuna_alta', 'par',   '1',    120.00, 130.00,  99, true],
            [831, 'Tribuna Alta Par 2',           'tribuna_alta', 'par',   '2',    120.00, 130.00,  91, true],
            [832, 'Tribuna Alta Par 3',           'tribuna_alta', 'par',   '3',    120.00, 130.00,  34, true],
            [833, 'Tribuna Alta Par 4',           'tribuna_alta', 'par',   '4',    120.00, 130.00,   1, true],
            [834, 'Tribuna Alta Par 5',           'tribuna_alta', 'par',   '5',    null,   null,     0, false],
            [835, 'Tribuna Baja Par 1',           'tribuna_baja', 'par',   '1',    120.00, 130.00,  48, true],
            [836, 'Tribuna Baja Par 2',           'tribuna_baja', 'par',   '2',    120.00, 130.00,  86, true],
            [837, 'Tribuna Baja Par 3',           'tribuna_baja', 'par',   '3',    120.00, 130.00,  85, true],
            [838, 'Tribuna Baja Par 4',           'tribuna_baja', 'par',   '4',    120.00, 130.00,  54, true],
            [839, 'Tribuna Baja Par 5',           'tribuna_baja', 'par',   '5',    null,   null,     0, false],
            [840, 'Tribuna Baja Par 6',           'tribuna_baja', 'par',   '6',    120.00, 130.00,  44, true],
            [841, 'Tribuna Baja Par 7',           'tribuna_baja', 'par',   '7',    120.00, 130.00,   5, true],
            [842, 'Tribuna Alta Impar 6',         'tribuna_alta', 'impar', '6',    120.00, 130.00,   3, true],
            [843, 'Tribuna Alta Impar 7',         'tribuna_alta', 'impar', '7',    120.00, 130.00,   5, true],
            [844, 'Tribuna Alta Impar 8',         'tribuna_alta', 'impar', '8',    120.00, 130.00,  65, true],
            [845, 'Tribuna Alta Impar 9',         'tribuna_alta', 'impar', '9',    120.00, 130.00, 119, true],
            [846, 'Tribuna Alta Impar 10',        'tribuna_alta', 'impar', '10',   120.00, 130.00,  67, true],
            [847, 'Reservado',                    'otros',        'none',  null,   null,   null,     0, false],
            [848, 'Tribuna Baja Impar 8',         'tribuna_baja', 'impar', '8',    120.00, 130.00,   3, true],
            [849, 'Tribuna Baja Impar 9',         'tribuna_baja', 'impar', '9',    120.00, 130.00,  35, true],
            [850, 'Tribuna Baja Impar 10',        'tribuna_baja', 'impar', '10',   120.00, 130.00,  64, true],
            [851, 'Tribuna Baja Impar 11',        'tribuna_baja', 'impar', '11',   120.00, 130.00,  84, true],
            [852, 'Tribuna Baja Impar 12',        'tribuna_baja', 'impar', '12',   120.00, 130.00,  85, true],
            [853, 'Tribuna Baja Impar 13',        'tribuna_baja', 'impar', '13',   120.00, 130.00,  14, true],
            [854, 'Tribuna Baja Impar 14',        'tribuna_baja', 'impar', '14',   120.00, 130.00,  16, true],
            [855, 'Tribuna Alta Par A',           'tribuna_alta', 'par',   'A',    null,   null,     0, false],
            [856, 'Tribuna Alta Impar B',         'tribuna_alta', 'impar', 'B',    120.00, 130.00,   3, true],
            [857, 'Palco de Honor Par',           'palco',        'par',   null,   null,   null,     0, false],
            [858, 'Preferente Par 1',             'preferente',   'par',   '1',     75.00,  80.00,  91, true],
            [859, 'Preferente Par 2',             'preferente',   'par',   '2',     75.00,  80.00, 105, true],
            [860, 'Preferente Par 3',             'preferente',   'par',   '3',     75.00,  80.00, 134, true],
            [861, 'Preferente Par 4',             'preferente',   'par',   '4',     75.00,  80.00, 102, true],
            [862, 'Preferente Par 5',             'preferente',   'par',   '5',     75.00,  80.00,  76, true],
            [863, 'Preferente Par 6',             'preferente',   'par',   '6',     75.00,  80.00,  40, true],
            [864, 'Preferente Par 7',             'preferente',   'par',   '7',     75.00,  80.00,   1, true],
            [865, 'Preferente Impar 8',           'preferente',   'impar', '8',    null,   null,     0, false],
            [866, 'Preferente Impar 9',           'preferente',   'impar', '9',     75.00,  80.00,  15, true],
            [867, 'Preferente Impar 10',          'preferente',   'impar', '10',    75.00,  80.00,  44, true],
            [868, 'Preferente Impar 11',          'preferente',   'impar', '11',    75.00,  80.00,  73, true],
            [869, 'Preferente Impar 12',          'preferente',   'impar', '12',    75.00,  80.00, 107, true],
            [870, 'Preferente Impar 13',          'preferente',   'impar', '13',    75.00,  80.00, 104, true],
            [871, 'Preferente Impar 14',          'preferente',   'impar', '14',    75.00,  80.00,  91, true],
            [872, 'Fondo Norte Par 1',            'fondo_norte',  'par',   '1',     60.00,  60.00,  72, true],
            [873, 'Fondo Norte Par 2',            'fondo_norte',  'par',   '2',     60.00,  60.00, 111, true],
            [874, 'Fondo Norte Par 3',            'fondo_norte',  'par',   '3',     60.00,  60.00, 112, true],
            [875, 'Fondo Norte Par 4',            'fondo_norte',  'par',   '4',     60.00,  60.00,  72, true],
            [876, 'Fondo Norte Impar 5',          'fondo_norte',  'impar', '5',     60.00,  60.00,  68, true],
            [877, 'Fondo Norte Impar 6',          'fondo_norte',  'impar', '6',     60.00,  60.00, 143, true],
            [878, 'Fondo Norte Impar 7',          'fondo_norte',  'impar', '7',     60.00,  60.00, 144, true],
            [879, 'Fondo Norte Impar 8',          'fondo_norte',  'impar', '8',     60.00,  60.00, 139, true],
            [880, 'Fondo Norte Impar 9',          'fondo_norte',  'impar', '9',     60.00,  60.00, 115, true],
            [881, 'Fondo Sur Par 1',              'fondo_sur',    'par',   '1',     60.00,  60.00, 108, true],
            [882, 'Fondo Sur Par 2',              'fondo_sur',    'par',   '2',     60.00,  60.00, 125, true],
            [883, 'Fondo Sur Par 3',              'fondo_sur',    'par',   '3',     60.00,  60.00, 100, true],
            [884, 'Fondo Sur Par 4',              'fondo_sur',    'par',   '4',     60.00,  60.00,  91, true],
            [885, 'Fondo Sur Par 5',              'fondo_sur',    'par',   '5',     60.00,  60.00,  10, true],
            [886, 'Fondo Sur Impar 6',            'fondo_sur',    'impar', '6',     60.00,  60.00,  17, true],
            [887, 'Fondo Sur Impar 7',            'fondo_sur',    'impar', '7',     60.00,  60.00,  47, true],
            [888, 'Fondo Sur Impar 8',            'fondo_sur',    'impar', '8',     60.00,  60.00,  97, true],
            [889, 'Fondo Sur Impar 9',            'fondo_sur',    'impar', '9',     60.00,  60.00,  77, true],
            [985, 'Reservado',                    'otros',        'none',  null,    null,   null,    0, false],
            [1389,'Palco de Honor Impar',         'palco',        'impar', null,    null,   null,    0, false],
            [3231,'Reservado',                    'otros',        'none',  null,    null,   null,    0, false],
            [5771,'Reservado',                    'otros',        'none',  null,    null,   null,    0, false],
            [5927,'Reservado',                    'otros',        'none',  null,    null,   null,    0, false],
            [6908,'Reservado',                    'otros',        'none',  null,    null,   null,    0, false],
        ];

        foreach ($data as [$reg, $name, $zone, $parity, $number, $padult, $pyouth, $cap, $avail]) {
            Sector::updateOrCreate(
                ['svg_region' => $reg],
                [
                    'name'        => $name,
                    'zone'        => $zone,
                    'parity'      => $parity,
                    'number'      => $number,
                    'price_adult' => $padult,
                    'price_youth' => $pyouth,
                    'capacity'    => $cap,
                    'available'   => $avail,
                    'color_hex'   => match ($zone) {
                        'tribuna_baja', 'tribuna_alta' => '#CF2E2E',
                        'preferente'                   => '#D4A24C',
                        'fondo_norte'                  => '#0A0A0A',
                        'fondo_sur'                    => '#1A1A1A',
                        'palco'                        => '#7C3AED',
                        default                        => '#9CA3AF',
                    },
                ]
            );
        }
    }
}
