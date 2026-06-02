<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Añade el campo `gestion_fee` (gastos de gestión).
     *
     * El 5% sobre el subtotal — análogo a la "service fee" de Ticketmaster,
     * Compralaentrada, ServiCaixa, etc. Cubre la emisión del ticket digital,
     * el QR firmado y el procesamiento de pago.
     *
     * `total` recalculado = subtotal + vat + shipping_cost + gestion_fee.
     * Los registros existentes quedan con 0 (no se les cobra retroactivamente).
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'gestion_fee')) {
                $table->decimal('gestion_fee', 10, 2)
                    ->default(0)
                    ->after('shipping_cost')
                    ->comment('Gastos de gestión (5% sobre subtotal). Incluido en total.');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'gestion_fee')) {
                $table->dropColumn('gestion_fee');
            }
        });
    }
};
