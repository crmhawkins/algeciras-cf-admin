<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
        DB::statement('ALTER TABLE customers DROP INDEX customers_socio_number_unique');
        DB::statement('ALTER TABLE customers ADD INDEX customers_socio_number_index (socio_number)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE customers DROP INDEX customers_socio_number_index');
        // No re-añadimos UNIQUE para evitar fallos si los datos ya contienen duplicados
    }
};
