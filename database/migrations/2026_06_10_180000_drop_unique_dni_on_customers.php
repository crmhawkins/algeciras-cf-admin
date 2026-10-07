<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * En un sistema de abonos una misma persona (mismo DNI) puede tener VARIOS
 * abonos (varios asientos). El padrón del club así lo refleja (ej. un titular
 * con 2-4 butacas, empresas/patrocinadores con DNI "-" y múltiples palcos).
 *
 * El UNIQUE en customers.dni impedía importar esos casos. Lo quitamos y
 * dejamos solo un índice normal para búsquedas.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Quitar el unique (Laravel lo nombró customers_dni_unique).
            try { $table->dropUnique('customers_dni_unique'); } catch (\Throwable $e) {}
        });
        Schema::table('customers', function (Blueprint $table) {
            try { $table->index('dni'); } catch (\Throwable $e) {}
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            try { $table->dropIndex(['dni']); } catch (\Throwable $e) {}
            try { $table->unique('dni'); } catch (\Throwable $e) {}
        });
    }
};
