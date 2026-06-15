<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

/**
 * Widget de Acciones Rápidas para el escritorio del admin.
 *
 * Atajos grandes y visibles para el día a día de taquilla:
 *  - 🔁 Renovación de abono
 *  - ✨ Abono nuevo (alta)
 *  - 🎫 Cobrar entrada
 *  - 🛍️ Cobrar producto tienda
 *
 * Cada botón apunta a /admin/cobro-manual con query params que
 * pre-seleccionan el modo cliente y filtran el tipo de producto.
 */
class AccionesRapidasWidget extends Widget
{
    protected string $view = 'filament.widgets.acciones-rapidas';

    /** Ocupa todo el ancho disponible (4 columnas en xl). */
    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -10;
}
