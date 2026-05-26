<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mvp_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('player_id')->constrained('players')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('voter_ip', 45)->nullable(); // anti-spam de invitados (IPv6 max 45)
            $table->timestamps();

            // Un socio solo vota una vez por partido
            $table->unique(['match_id', 'customer_id'], 'mvp_votes_match_customer_unique');

            $table->index(['match_id', 'player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mvp_votes');
    }
};
