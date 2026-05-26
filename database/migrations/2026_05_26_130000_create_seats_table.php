<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sector_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('row');          // 1-14
            $table->unsignedSmallInteger('number');       // 191, 193, ... (par o impar según sector)
            $table->enum('status', ['free','reserved','sold','blocked'])->default('free')->index();
            $table->timestamps();
            $table->unique(['sector_id', 'row', 'number']);
            $table->index(['sector_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('seats'); }
};
