<?php

namespace Database\Seeders;

use App\Models\ClubStaff;
use Illuminate\Database\Seeder;

class ClubStaffSeeder extends Seeder
{
    /**
     * Estructura del club según algecirasclubdefutbol.com/estructura
     */
    public function run(): void
    {
        $staff = [
            ['name' => 'Ramón Robert',   'role' => 'Consejero Delegado',         'department' => 'direccion',     'email' => 'r.robert@algecirasclubdefutbol.com'],
            ['name' => 'Miguel Ligero',  'role' => 'Coordinador Academia',       'department' => 'academia',      'email' => 'cantera@algecirasclubdefutbol.com'],
            ['name' => 'Laura Rubio',    'role' => 'Instalaciones y Rel. RFEF',  'department' => 'instalaciones', 'email' => 'l.rubio@algecirasclubdefutbol.com'],
            ['name' => 'Sandra Ternero', 'role' => 'Ticketing',                  'department' => 'ticketing',     'email' => 's.ternero@algecirasclubdefutbol.com'],
            ['name' => 'Finanzas y RRHH','role' => 'Finanzas y Recursos Humanos','department' => 'rrhh',          'email' => 'rrhh@algecirasclubdefutbol.com'],
            ['name' => 'Protocolo',      'role' => 'Protocolo',                  'department' => 'protocolo',     'email' => 'protocolo@algecirasclubdefutbol.com'],
            ['name' => 'Dirección Deportiva', 'role' => 'Dirección Deportiva',   'department' => 'd_deportiva',   'email' => 'd.deportiva@algecirasclubdefutbol.com'],
        ];

        foreach ($staff as $i => $s) {
            $s['sort_order'] = $i;
            ClubStaff::updateOrCreate(['email' => $s['email']], $s);
        }
    }
}
