<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QR del sistema antiguo del club (token de 12 caracteres por abonado).
 *
 * El validador de la puerta debe leer TANTO estos QR antiguos (carnets PVC
 * ya impresos / app antigua) COMO los que genera nuestra plataforma para
 * altas nuevas (uuid + qr_token). Por eso guardamos el QR legacy aparte.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('tickets', 'legacy_qr')) {
                $table->string('legacy_qr', 40)->nullable()->index()->after('qr_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'legacy_qr')) {
                $table->dropIndex(['legacy_qr']);
                $table->dropColumn('legacy_qr');
            }
        });
    }
};
