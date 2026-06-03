<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Histórico de notificaciones enviadas a abonados antes/después de cada
 * partido (equivale a `notificaciones_partidos` legacy).
 *
 * IMPORTANTE: esta tabla guarda SOLO el registro histórico de envíos pasados.
 * No dispara nuevas notificaciones — los jobs nuevos se gestionan en otra
 * parte del sistema. Por eso `sent_at` puede estar en el pasado y nunca
 * deberá ser usada para "enviar pendientes".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_notifications', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('legacy_id')->nullable()->unique();
            $t->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('football_match_id')->nullable()->constrained('matches')->nullOnDelete();
            $t->dateTime('sent_at')->nullable();
            $t->string('channel', 32)->nullable(); // 'push' | 'email' | 'sms'
            $t->timestamps();

            $t->index(['football_match_id', 'channel']);
            $t->index('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_notifications');
    }
};
