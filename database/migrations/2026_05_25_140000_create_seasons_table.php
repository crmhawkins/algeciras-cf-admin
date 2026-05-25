<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->string('name', 16)->unique(); // "2026-27"
            $table->date('start_at');
            $table->date('end_at');
            $table->boolean('current')->default(false)->index();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('seasons'); }
};
