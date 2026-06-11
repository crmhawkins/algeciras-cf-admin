<?php

namespace Database\Seeders;

use App\Models\AbonoPrecio;
use Illuminate\Database\Seeder;

/**
 * Precios oficiales de abono temporada — copiados EXACTOS del CRM del proveedor
 * (compralaentrada /admin/abonos/precios, leído 2026-06-11).
 *
 * El proveedor NO vende "Fondo Norte alta nueva" ni infantil en Palco: no se inventan.
 * Idempotente: keyed por provider_id.
 */
class AbonoPreciosSeeder extends Seeder
{
    public function run(): void
    {
        // [provider_id, descripcion, zona, modalidad, es_infantil, precio, edad_min, edad_max, renovacion]
        $tipos = [
            [10595, 'TRIBUNA RENOVACIÓN',             'tribuna_alta', 'renovacion', false, 200, 15, 99, true],
            [10596, 'PREFERENCIA RENOVACIÓN',         'preferente',   'renovacion', false, 130, 15, 99, true],
            [10597, 'FONDO SUR RENOVACIÓN',           'fondo_sur',    'renovacion', false, 100, 15, 99, true],
            [10598, 'FONDO NORTE RENOVACIÓN',         'fondo_norte',  'renovacion', false, 100, 15, 99, true],
            [10599, 'TRIBUNA INFANTIL RENOVACIÓN',    'tribuna_alta', 'renovacion', true,  115,  4, 14, true],
            [10600, 'PREFERENCIA RENOVACIÓN INFANTIL','preferente',   'renovacion', true,   75,  4, 14, true],
            [10601, 'FONDO SUR RENOVACIÓN INFANTIL',  'fondo_sur',    'renovacion', true,   55,  4, 14, true],
            [10602, 'FONDO NORTE RENOVACIÓN INFANTIL','fondo_norte',  'renovacion', true,   55,  4, 14, true],
            [10603, 'PALCO RENOVACIÓN',               'palco',        'renovacion', false, 625, 18, 99, true],
            [10604, 'PALCO ALTA NUEVA',               'palco',        'nueva',      false, 650, 18, 99, false],
            [10622, 'TRIBUNA ALTA NUEVA',             'tribuna_alta', 'nueva',      false, 235, 15, 99, false],
            [10623, 'PREFERENCIA ALTA NUEVA',         'preferente',   'nueva',      false, 150, 15, 99, false],
            [10624, 'FONDO SUR ALTA NUEVA',           'fondo_sur',    'nueva',      false, 125, 15, 99, false],
            [10625, 'TRIBUNA ALTA INFANTIL',          'tribuna_alta', 'nueva',      true,  135,  4, 13, false],
            [10626, 'PREFERENCIA ALTA INFANTIL',      'preferente',   'nueva',      true,   85,  4, 14, false],
            [10627, 'FONDO SUR ALTA INFANTIL',        'fondo_sur',    'nueva',      true,   65,  4, 14, false],
            [10628, 'FONDO NORTE ALTA INFANTIL',      'fondo_norte',  'nueva',      true,   65,  4, 14, false],
        ];

        foreach ($tipos as $i => $t) {
            AbonoPrecio::updateOrCreate(
                ['provider_id' => $t[0]],
                [
                    'descripcion' => $t[1],
                    'zona'        => $t[2],
                    'modalidad'   => $t[3],
                    'es_infantil' => $t[4],
                    'precio'      => $t[5],
                    'edad_min'    => $t[6],
                    'edad_max'    => $t[7],
                    'renovacion'  => $t[8],
                    'activo'      => true,
                    'pago_plazos' => false,
                    'stock'       => null,
                    'orden'       => $i + 1,
                ]
            );
        }
    }
}
