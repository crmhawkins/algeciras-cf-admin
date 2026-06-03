<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permite tickets sin Order asociado, necesario para:
 *  - Abonos importados de la BD legacy del club anterior
 *  - Cobros manuales hechos desde admin sin pasar por checkout completo
 */
return new class extends Migration
{
    public function up(): void
    {
        // Usamos SQL directo porque Doctrine DBAL puede no estar disponible
        // y MariaDB acepta MODIFY COLUMN nativo.
        DB::statement('ALTER TABLE tickets MODIFY order_item_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        // No revertimos a NOT NULL para evitar romper si hay tickets sin order_item_id
        // ya creados. Si se necesita, el operador lo hará a mano.
    }
};
