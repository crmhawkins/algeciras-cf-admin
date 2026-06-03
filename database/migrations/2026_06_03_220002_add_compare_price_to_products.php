<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade `price_compare_at` para mostrar precios tachados (ofertas) en la
 * tienda. Equivale a `precioAnterior` de la BD legacy.
 * Y `legacy_id` para mapeo legacy -> nuevo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $t) {
            if (! Schema::hasColumn('products', 'price_compare_at')) {
                $t->decimal('price_compare_at', 10, 2)->nullable()->after('price');
            }
            if (! Schema::hasColumn('products', 'legacy_id')) {
                $t->unsignedBigInteger('legacy_id')->nullable()->unique()->after('id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $t) {
            $t->dropColumn(['price_compare_at', 'legacy_id']);
        });
    }
};
