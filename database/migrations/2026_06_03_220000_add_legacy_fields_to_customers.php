<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade campos para migración desde la BD legacy del club (`algeciras_db`):
 *  - `gender`: campo histórico, no usado en el flujo nuevo pero presente
 *    en la BD vieja del cliente.
 *  - `legacy_user_id`: id original en tabla `usuarios`. Sirve para mapear
 *    referencias cruzadas (abonos.usuarioId, etc.) y permite re-ejecutar
 *    la migración sin duplicar.
 *  - `legacy_socio_id`: codigoAbonado original (entero) del cliente,
 *    distinto del `socio_number` (que usamos como string).
 *
 * Todos NULLABLE — no afectan a customers creados con el flujo nuevo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $t) {
            if (! Schema::hasColumn('customers', 'gender')) {
                $t->string('gender', 20)->nullable()->after('birth_date');
            }
            if (! Schema::hasColumn('customers', 'legacy_user_id')) {
                $t->unsignedBigInteger('legacy_user_id')->nullable()->unique()->after('notes');
            }
            if (! Schema::hasColumn('customers', 'legacy_socio_id')) {
                $t->unsignedBigInteger('legacy_socio_id')->nullable()->index()->after('legacy_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $t) {
            $t->dropColumn(['gender', 'legacy_user_id', 'legacy_socio_id']);
        });
    }
};
