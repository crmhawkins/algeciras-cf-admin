<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tablas para el área de socio escalable (web + app).
 *
 * - notification_preferences: opt-in/opt-out por categoría
 * - coupons: cupones de descuento (admin define)
 * - customer_coupons: pivote con estado de canje
 * - match_attendances: registro asistencia al estadio
 */
return new class extends Migration
{
    public function up(): void
    {
        // Preferencias notificación push/email por categoría
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('category', 64); // goals, lineups, news, store_offers, fanzone, matchday_reminder
            $table->boolean('email_enabled')->default(true);
            $table->boolean('push_enabled')->default(true);
            $table->timestamps();
            $table->unique(['customer_id', 'category']);
        });

        // Cupones de descuento (admin crea, sistema reparte)
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->enum('type', ['percent', 'fixed', 'gift']);
            $table->decimal('value', 10, 2)->default(0); // % o € según type
            $table->string('image')->nullable();
            $table->enum('target_tier', ['all', 'abonado', 'abonado_vip', 'peñista'])->default('all');
            $table->unsignedSmallInteger('max_uses_per_customer')->default(1);
            $table->unsignedInteger('total_stock')->nullable(); // null = ilimitado
            $table->unsignedInteger('used_count')->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['active', 'target_tier']);
        });

        // Pivote socio↔cupón con estado de canje
        Schema::create('customer_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['available', 'redeemed', 'expired'])->default('available');
            $table->timestamp('redeemed_at')->nullable();
            $table->string('redeemed_via', 32)->nullable(); // 'web', 'app', 'tienda_física'
            $table->timestamps();
            $table->index(['customer_id', 'status']);
        });

        // Asistencia: cada vez que el QR de un abono se valida en torno
        Schema::create('match_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('checked_in_at')->useCurrent();
            $table->string('gate', 32)->nullable(); // PUERTA 5, etc.
            $table->timestamps();
            $table->unique(['customer_id', 'match_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_attendances');
        Schema::dropIfExists('customer_coupons');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('notification_preferences');
    }
};
