<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crea el usuario operador de puerta para la PWA de validación de QRs.
 *
 *   email    : operador@algecirascf.es
 *   password : algeciras2026
 *   role     : operator
 *
 * ⚠️ CREDENCIALES DE DEMO ⚠️
 * Estas credenciales son SOLO para entorno de desarrollo y demo del cliente.
 * ANTES DE PASAR A PRODUCCIÓN:
 *   1. Cambiar el password a uno robusto (gestor de contraseñas).
 *   2. Considerar crear un usuario por persona física que vaya a operar
 *      la puerta, no compartir credenciales entre operadores (para que el
 *      log de Attendance pueda atribuir cada validación a su responsable).
 *
 * Idempotente: usa `updateOrCreate` por email, no duplica si ya existe.
 */
class OperatorSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'operador@algecirascf.es'],
            [
                'name'     => 'Operador puerta',
                'password' => Hash::make('algeciras2026'),
                'role'     => 'operator',
            ]
        );
    }
}
