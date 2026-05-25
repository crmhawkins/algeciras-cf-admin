<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_type', 16);  // snapshot del type para histórico
            $table->string('name', 200);          // snapshot
            $table->string('sku', 64)->nullable();
            $table->unsignedInteger('qty')->default(1);
            $table->decimal('unit_price', 10, 2);    // con IVA
            $table->unsignedTinyInteger('vat_rate')->default(21);
            $table->decimal('subtotal', 10, 2);       // sin IVA
            $table->decimal('vat_amount', 10, 2);
            $table->decimal('total', 10, 2);
            $table->json('meta')->nullable(); // talla, color, match_info, season_info
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('order_items'); }
};
