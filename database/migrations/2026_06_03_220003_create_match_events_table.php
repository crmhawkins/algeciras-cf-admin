<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Eventos individuales de cada partido (goles, tarjetas, cambios...).
 * Equivale a la tabla `evento_partidos` de la BD legacy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_events', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('legacy_id')->nullable()->unique();
            $t->foreignId('football_match_id')->constrained('matches')->cascadeOnDelete();
            $t->unsignedSmallInteger('minute')->nullable();
            $t->string('type', 32);            // 'gol' | 'cambio' | 'tarjeta_amarilla' | 'tarjeta_roja' | 'penalti' | etc.
            $t->string('player_name')->nullable();
            $t->string('player_in')->nullable();
            $t->string('player_out')->nullable();
            $t->string('team', 32)->nullable();// 'local' | 'visitante'
            $t->string('image')->nullable();
            $t->timestamps();

            $t->index(['football_match_id', 'minute']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_events');
    }
};
