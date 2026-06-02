<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // qr_token ya existe en la migracion original (string 96 unique).
            // Para abonos almacenamos el HMAC firmado; para entradas se
            // recalcula por partido y por eso se complementa con qr_secret.
            if (! Schema::hasColumn('tickets', 'qr_token')) {
                $table->string('qr_token', 64)->nullable()->index()->after('uuid');
            }

            // Semilla aleatoria por ticket usada para QRs rotativos (entradas
            // por partido). Permite invalidar el QR del partido anterior
            // simplemente rotando la semilla.
            if (! Schema::hasColumn('tickets', 'qr_secret')) {
                $table->string('qr_secret', 64)->nullable()->after('qr_token');
            }

            // qr_image_path ya existe en la migracion original. Lo dejamos
            // como guard por si en otro entorno se borra.
            if (! Schema::hasColumn('tickets', 'qr_image_path')) {
                $table->string('qr_image_path', 255)->nullable()->after('qr_secret');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'qr_secret')) {
                $table->dropColumn('qr_secret');
            }
        });
    }
};
