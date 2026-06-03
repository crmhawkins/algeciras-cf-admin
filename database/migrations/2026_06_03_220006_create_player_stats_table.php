<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stats por jugador y temporada (equivale a `jugador_stats` legacy).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_stats', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('legacy_id')->nullable()->unique();
            $t->foreignId('player_id')->constrained()->cascadeOnDelete();
            $t->string('season_string', 16);  // "2024-25"
            $t->unsignedInteger('goals')->default(0);
            $t->unsignedInteger('assists')->default(0);
            $t->unsignedInteger('minutes_played')->default(0);
            $t->unsignedInteger('appearances')->default(0);
            $t->unsignedInteger('starting_xi')->default(0);
            $t->unsignedInteger('yellow_cards')->default(0);
            $t->unsignedInteger('red_cards')->default(0);
            $t->unsignedInteger('clean_sheets')->default(0); // porteros
            $t->json('extra')->nullable();   // datos adicionales no estandarizados
            $t->timestamps();

            $t->unique(['player_id', 'season_string']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('player_stats');
    }
};
