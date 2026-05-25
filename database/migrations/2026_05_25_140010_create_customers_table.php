<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('first_name', 96);
            $table->string('last_name', 96);
            $table->string('email', 160)->index();
            $table->string('phone', 32)->nullable();
            $table->string('dni', 24)->nullable()->unique(); // formato 12345678X
            $table->date('birth_date')->nullable();
            $table->string('address', 200)->nullable();
            $table->string('city', 96)->nullable();
            $table->string('province', 96)->nullable();
            $table->string('postal_code', 12)->nullable();
            $table->string('country', 64)->default('España');
            $table->boolean('is_socio')->default(false)->index();
            $table->unsignedInteger('socio_number')->nullable()->unique();
            $table->date('socio_since')->nullable();
            $table->string('language', 8)->default('es');
            $table->boolean('newsletter_optin')->default(false);
            $table->boolean('whatsapp_optin')->default(false);
            $table->json('notes')->nullable(); // internas admin
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('customers'); }
};
