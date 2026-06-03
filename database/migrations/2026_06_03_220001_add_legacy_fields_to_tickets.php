<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos para migración legacy de la tabla `abonos`:
 *  - `legacy_id`: id original en `abonos`. Necesario para resolver
 *    `notificaciones_partidos.abonoId` y reidempotencia.
 *  - `price_paid`: precio histórico que pagó el abonado por su abono.
 *    Útil para histórico contable (en flujo nuevo el precio vive en Order).
 *  - `legacy_codigo_acceso`: PIN / código de acceso de hasta 12 chars que
 *    se usaba en la app vieja para entrar al portal del estadio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $t) {
            if (! Schema::hasColumn('tickets', 'legacy_id')) {
                $t->unsignedBigInteger('legacy_id')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('tickets', 'price_paid')) {
                $t->decimal('price_paid', 10, 2)->nullable()->after('valid_until');
            }
            if (! Schema::hasColumn('tickets', 'legacy_codigo_acceso')) {
                $t->string('legacy_codigo_acceso', 16)->nullable()->after('price_paid');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $t) {
            $t->dropColumn(['legacy_id', 'price_paid', 'legacy_codigo_acceso']);
        });
    }
};
