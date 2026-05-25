<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('match_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('season_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('uuid')->unique();  // base del QR
            $table->string('qr_token', 96)->unique(); // hash firmado para validar
            $table->string('qr_image_path')->nullable(); // PNG generado
            $table->enum('status', ['issued','used','cancelled','expired'])->default('issued')->index();
            $table->string('holder_name', 160)->nullable(); // por si la entrada va a otra persona
            $table->string('holder_dni', 24)->nullable();
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('used_gate', 32)->nullable(); // puerta de acceso
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('tickets'); }
};
