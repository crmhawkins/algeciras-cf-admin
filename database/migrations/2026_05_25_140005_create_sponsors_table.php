<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sponsors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('slug', 96)->unique();
            $table->enum('tier', ['tecnico','principal','main','secundario','partner','colaborador'])->index();
            $table->string('logo')->nullable();
            $table->string('logo_dark')->nullable(); // versión para fondos oscuros
            $table->string('url', 255)->nullable();
            $table->boolean('invert_on_dark')->default(false);
            $table->json('description')->nullable();
            $table->date('contract_start')->nullable();
            $table->date('contract_end')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('sponsors'); }
};
