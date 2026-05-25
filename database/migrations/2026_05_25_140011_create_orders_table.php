<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 24)->unique(); // ACF-2026-000001
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_email', 160)->nullable(); // si compra como invitado
            $table->enum('status', ['pending','paid','fulfilled','cancelled','refunded'])->default('pending')->index();
            $table->enum('channel', ['web','admin','tpv'])->default('web');
            $table->decimal('subtotal', 10, 2)->default(0);  // sin IVA
            $table->decimal('vat', 10, 2)->default(0);       // total IVA
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);     // a cobrar
            $table->string('currency', 3)->default('EUR');
            $table->string('payment_gateway', 16)->nullable(); // stripe/redsys
            $table->string('payment_intent_id', 128)->nullable()->index(); // stripe pi_xxx o redsys order
            $table->json('shipping_address')->nullable();
            $table->json('billing_address')->nullable();
            $table->string('tracking_carrier', 32)->nullable();
            $table->string('tracking_number', 96)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('fulfilled_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('orders'); }
};
