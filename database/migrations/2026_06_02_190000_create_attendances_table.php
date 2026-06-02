<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla `attendances` — registro per-ticket de entrada al estadio.
 *
 * Diferente de `match_attendances` (que es per-customer) porque:
 *   - Permite a un mismo cliente entrar con varios tickets distintos
 *     al mismo partido (acompañantes con entradas a su nombre, etc.).
 *   - Almacena gate_id (pistola/puerta) + meta arbitrario.
 *   - Tracquea quién (operador) lo validó.
 *
 * La UNIQUE(ticket_id, match_id) es la salvaguarda contra DOBLE ENTRADA:
 * un mismo abono no puede registrar dos asistencias al mismo partido.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->timestamp('scanned_at')->useCurrent();
            $table->foreignId('scanned_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('gate_id', 32)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            // Clave del control de doble entrada al mismo partido.
            $table->unique(['ticket_id', 'match_id']);

            // Para stats live de aforo agrupadas por sector/zona.
            $table->index(['match_id', 'scanned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
