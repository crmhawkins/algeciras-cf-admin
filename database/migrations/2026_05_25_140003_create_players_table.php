<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('dorsal')->nullable()->index(); // 1-99
            $table->string('slug', 96)->unique();
            $table->string('display_name', 96);  // "IVÁN MORENO"
            $table->string('full_name', 160)->nullable();
            $table->enum('position', ['portero','defensa','centrocampista','delantero','tecnico'])->index();
            $table->string('photo')->nullable();
            $table->string('photo_action')->nullable(); // foto en partido
            $table->date('birth_date')->nullable();
            $table->string('birth_place', 96)->nullable();
            $table->string('nationality', 64)->default('España');
            $table->unsignedSmallInteger('height_cm')->nullable();
            $table->unsignedSmallInteger('weight_kg')->nullable();
            $table->string('preferred_foot', 16)->nullable(); // derecha/izquierda/ambas
            $table->json('bio')->nullable(); // translatable
            $table->string('instagram', 96)->nullable();
            $table->string('x_handle', 96)->nullable();
            $table->date('joined_at')->nullable();
            $table->date('contract_end')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->boolean('captain')->default(false);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('players'); }
};
