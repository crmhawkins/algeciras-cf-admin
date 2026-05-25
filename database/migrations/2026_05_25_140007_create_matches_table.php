<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('matchday')->nullable()->index(); // jornada
            $table->string('competition', 48)->default('Primera RFEF'); // Primera RFEF / Copa Federación / Copa del Rey
            $table->string('opponent', 120);
            $table->string('opponent_logo')->nullable();
            $table->enum('venue', ['home','away'])->index();
            $table->string('stadium', 120)->default('Nuevo Mirador');
            $table->dateTime('kickoff_at')->index();
            $table->enum('status', ['scheduled','live','finished','postponed','cancelled'])->default('scheduled')->index();
            $table->unsignedTinyInteger('home_score')->nullable();
            $table->unsignedTinyInteger('away_score')->nullable();
            $table->string('broadcast', 80)->nullable(); // canal TV/streaming
            $table->string('ticket_external_url')->nullable(); // por si se vende fuera
            $table->json('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('matches'); }
};
