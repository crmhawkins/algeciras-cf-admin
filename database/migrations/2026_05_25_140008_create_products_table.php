<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 48)->unique();
            $table->string('slug', 160)->unique();
            // ---- TIPO POLIMÓRFICO ----
            $table->enum('type', ['merch','abono','entrada'])->index();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            // ---- Datos comunes ----
            $table->json('name');       // translatable
            $table->json('description')->nullable();
            $table->json('short_description')->nullable();
            $table->decimal('price', 10, 2);          // precio principal (con IVA)
            $table->decimal('compare_at_price', 10, 2)->nullable(); // precio antes (rebaja)
            $table->unsignedTinyInteger('vat_rate')->default(21);   // 21 merch | 10 entrada/abono
            $table->string('image')->nullable();
            $table->json('gallery')->nullable(); // array de paths
            $table->boolean('active')->default(true)->index();
            $table->boolean('featured')->default(false)->index();
            $table->unsignedTinyInteger('sort_order')->default(0);
            // ---- Solo merch ----
            $table->boolean('has_variants')->default(false);
            $table->boolean('ship_required')->default(true);
            $table->unsignedInteger('stock')->nullable(); // null = unlimited si no tiene variantes
            $table->decimal('weight_kg', 6, 3)->nullable();
            // ---- Solo entrada ----
            $table->foreignId('match_id')->nullable()->constrained()->nullOnDelete();
            // ---- Solo abono ----
            $table->foreignId('season_id')->nullable()->constrained()->nullOnDelete();
            // ---- Compartido entrada+abono ----
            $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('capacity')->nullable(); // plazas disponibles (entrada/abono)
            $table->unsignedInteger('sold')->default(0); // contador vendidas
            $table->timestamp('sale_starts_at')->nullable();
            $table->timestamp('sale_ends_at')->nullable();
            $table->boolean('socios_only')->default(false); // ej. preventa solo abonados
            $table->timestamps();
            $table->index(['type','active','featured']);
        });
    }
    public function down(): void { Schema::dropIfExists('products'); }
};
