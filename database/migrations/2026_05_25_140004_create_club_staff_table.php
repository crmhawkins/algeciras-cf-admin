<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('club_staff', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('role', 96); // "Consejero Delegado", "Coord. Academia", ...
            $table->enum('department', [
                'direccion','academia','instalaciones','rrhh','ticketing',
                'protocolo','d_deportiva','marketing','medico','otro'
            ])->default('otro')->index();
            $table->string('email', 120)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('photo')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('visible_web')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('club_staff'); }
};
