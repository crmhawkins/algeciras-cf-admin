<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Butaca asignada cuando es abono nominativo (alta presencial).
            // Nullable porque entradas de partido suelto no necesariamente
            // tienen butaca pre-asignada en BD (se usa zone_id).
            $table->foreignId('seat_id')
                ->nullable()
                ->after('zone_id')
                ->constrained('seats')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seat_id');
        });
    }
};
