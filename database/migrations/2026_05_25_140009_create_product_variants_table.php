<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 64)->unique();
            $table->string('size', 16)->nullable();  // XS, S, M, L, XL, XXL, 36, 38, 40...
            $table->string('color', 32)->nullable();
            $table->decimal('price_override', 10, 2)->nullable();
            $table->integer('stock')->default(0);
            $table->boolean('active')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['product_id','size','color']);
        });
    }
    public function down(): void { Schema::dropIfExists('product_variants'); }
};
