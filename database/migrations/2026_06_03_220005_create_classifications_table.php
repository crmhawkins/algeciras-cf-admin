<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshots de clasificación liga (equivale a `clasificacion` legacy).
 *
 * Cada fila es una posición en la tabla de un equipo en una temporada.
 * El conjunto de filas con misma `season_id` o `season_string` representa
 * el estado de la liga en un momento. Permite historizar varios snapshots
 * por temporada si se quiere (campo `snapshot_date`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classifications', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('legacy_id')->nullable()->unique();
            $t->foreignId('season_id')->nullable()->constrained()->nullOnDelete();
            $t->string('season_string', 16)->nullable(); // "2025/26" cuando no haya season
            $t->unsignedSmallInteger('position');         // 1..N
            $t->string('team_name', 120);
            $t->string('team_logo')->nullable();
            $t->unsignedInteger('played')->default(0);   // PJ
            $t->unsignedInteger('wins')->default(0);
            $t->unsignedInteger('draws')->default(0);
            $t->unsignedInteger('losses')->default(0);
            $t->unsignedInteger('goals_for')->default(0);
            $t->unsignedInteger('goals_against')->default(0);
            $t->unsignedInteger('points')->default(0);
            $t->date('snapshot_date')->nullable();
            $t->timestamps();

            $t->index(['season_id', 'position']);
            $t->index('snapshot_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classifications');
    }
};
