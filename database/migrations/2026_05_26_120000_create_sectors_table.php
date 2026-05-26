<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sectors', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('svg_region')->unique();   // data-region del SVG original (ej. 830, 836, 858)
            $table->string('name', 80);                         // "TRIBUNA BAJA PAR 2"
            $table->string('zone', 32)->index();                // tribuna_baja / tribuna_alta / preferente / fondo_norte / fondo_sur / palco / otros
            $table->enum('parity', ['par','impar','none'])->default('none');
            $table->string('number', 8)->nullable();            // "1", "14", "A", "B" (algunos sectores usan letra)
            $table->decimal('price_adult', 8, 2)->nullable();   // 120.00 / 75.00 / 60.00
            $table->decimal('price_youth', 8, 2)->nullable();   // 130.00 / 80.00 / 60.00 (infantil)
            $table->unsignedInteger('capacity')->default(0);    // plazas libres
            $table->boolean('available')->default(true);        // false = palco VIP / zona prensa / acceso
            $table->string('color_hex', 9)->nullable();         // color asignado en SVG
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('sectors'); }
};
