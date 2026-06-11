<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Configurar precios de los abonos" — réplica del módulo del proveedor
 * (compralaentrada /admin/abonos/precios). Cada fila es un TIPO de abono:
 * zona × modalidad (alta nueva / renovación) × adulto/infantil, con su precio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abono_precios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id')->nullable()->index(); // id del tipo en el CRM proveedor (referencia)
            $table->string('descripcion');                  // "TRIBUNA RENOVACIÓN"
            $table->string('zona', 32)->index();            // tribuna_alta / preferente / fondo_sur / fondo_norte / palco
            $table->enum('modalidad', ['nueva', 'renovacion'])->default('nueva');
            $table->boolean('es_infantil')->default(false);
            $table->decimal('precio', 8, 2)->default(0);
            $table->unsignedTinyInteger('edad_min')->nullable();
            $table->unsignedTinyInteger('edad_max')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('renovacion')->default(false);  // flag que el proveedor muestra en la columna RENOVACIÓN
            $table->boolean('pago_plazos')->default(false);
            $table->integer('stock')->nullable();           // null = sin límite
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abono_precios');
    }
};
