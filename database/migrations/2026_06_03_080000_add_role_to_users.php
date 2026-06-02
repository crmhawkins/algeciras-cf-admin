<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade columna `role` a la tabla `users` para distinguir entre:
 *   - admin    : acceso completo (panel + validador puerta).
 *   - operator : personal de puerta del estadio. Solo puede validar QRs
 *                y consultar stats live; entra a la PWA `/validator`.
 *   - customer : usuario final (default). Sin acceso a herramientas
 *                internas; consume la web/app móvil normal.
 *
 * Es idempotente: si la columna ya existe (porque se aplicó la migración
 * en otro entorno o se añadió a mano), no la vuelve a crear ni la borra.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'role')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'operator', 'customer'])
                ->default('customer')
                ->after('password');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
