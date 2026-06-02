<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extiende la tabla `coupons` para soportar el flujo de cupones en checkout
 * y enlaza los Orders al cupón aplicado.
 *
 * Idempotente: cada columna se añade sólo si no existe ya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('coupons', 'applies_to')) {
                $table->string('applies_to', 20)
                    ->default('all')
                    ->after('target_tier')
                    ->comment('A qué tipo de producto aplica: all|abonos|entradas|abonos_entradas|merch');
            }
            if (!Schema::hasColumn('coupons', 'min_order_total')) {
                $table->decimal('min_order_total', 10, 2)
                    ->nullable()
                    ->after('applies_to')
                    ->comment('Umbral mínimo del subtotal para que el cupón aplique.');
            }
            if (!Schema::hasColumn('coupons', 'max_discount')) {
                $table->decimal('max_discount', 10, 2)
                    ->nullable()
                    ->after('min_order_total')
                    ->comment('Tope absoluto del descuento en €.');
            }
            if (!Schema::hasColumn('coupons', 'customer_required')) {
                $table->boolean('customer_required')
                    ->default(false)
                    ->after('max_discount')
                    ->comment('true = sólo clientes con CustomerCoupon asignado pueden usarlo.');
            }
            if (!Schema::hasColumn('coupons', 'public_code')) {
                $table->boolean('public_code')
                    ->default(true)
                    ->after('customer_required')
                    ->comment('true = visible en el input del checkout; false = sólo vía campañas internas.');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'coupon_id')) {
                $table->unsignedBigInteger('coupon_id')
                    ->nullable()
                    ->after('gestion_fee');
                $table->foreign('coupon_id')
                    ->references('id')
                    ->on('coupons')
                    ->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'coupon_code')) {
                $table->string('coupon_code', 64)
                    ->nullable()
                    ->after('coupon_id')
                    ->comment('Snapshot del código en el momento de aplicar (por si el cupón se renombra/borra).');
            }
            if (!Schema::hasColumn('orders', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)
                    ->default(0)
                    ->after('coupon_code')
                    ->comment('Descuento aplicado en €. El total ya lo refleja.');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'coupon_id')) {
                try { $table->dropForeign(['coupon_id']); } catch (\Throwable $e) {}
                $table->dropColumn('coupon_id');
            }
            if (Schema::hasColumn('orders', 'coupon_code')) {
                $table->dropColumn('coupon_code');
            }
            if (Schema::hasColumn('orders', 'discount_amount')) {
                $table->dropColumn('discount_amount');
            }
        });

        Schema::table('coupons', function (Blueprint $table) {
            foreach (['applies_to', 'min_order_total', 'max_discount', 'customer_required', 'public_code'] as $col) {
                if (Schema::hasColumn('coupons', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
