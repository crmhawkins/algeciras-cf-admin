<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Quitamos el UNIQUE en customers.socio_number — los datos legacy del club
 * pueden tener varios customers compartiendo el mismo codigoAbonado
 * (familias bajo un mismo socio, errores históricos del CRM antiguo, etc.).
 * Pasa a ser INDEX normal (para búsqueda rápida pero sin restricción).
 */
return new class extends Migration
{
    public function up(): void
    {
        // SQLite (entorno local de desarrollo) no soporta ALTER TABLE ... INDEX.
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropUnique('customers_socio_number_unique');
                $table->index('socio_number', 'customers_socio_number_index');
            });
            return;
        }

        DB::statement('ALTER TABLE customers DROP INDEX customers_socio_number_unique');
        DB::statement('ALTER TABLE customers ADD INDEX customers_socio_number_index (socio_number)');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropIndex('customers_socio_number_index');
            });
            return;
        }

        DB::statement('ALTER TABLE customers DROP INDEX customers_socio_number_index');
        // No re-añadimos UNIQUE para evitar fallos si los datos ya contienen duplicados
    }
};
