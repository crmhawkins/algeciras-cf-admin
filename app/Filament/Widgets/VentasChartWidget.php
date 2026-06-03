<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

/**
 * Gráfica de barras — ventas de los últimos 30 días.
 *
 * Sólo cuenta pedidos en estado 'paid'. Usa el chart helper de Filament
 * (Chart.js bajo el capó), no requiere plugin externo.
 */
class VentasChartWidget extends ChartWidget
{
    protected ?string $heading = 'Ventas últimos 30 días';

    protected static ?int $sort = -50;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $hoy = now();
        $hace30 = $hoy->copy()->subDays(29)->startOfDay();

        // Sum diario en SQL
        $rows = Order::query()
            ->selectRaw('DATE(created_at) as dia, SUM(total) as total')
            ->whereIn('status', ['paid', 'fulfilled'])
            ->where('created_at', '>=', $hace30)
            ->groupBy('dia')
            ->pluck('total', 'dia');

        $labels = [];
        $data = [];
        for ($d = 0; $d < 30; $d++) {
            $date = $hace30->copy()->addDays($d)->toDateString();
            $labels[] = $hace30->copy()->addDays($d)->format('d/m');
            $data[]   = (float) ($rows[$date] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Ingresos (€)',
                    'data' => $data,
                    'backgroundColor' => 'rgba(227, 10, 44, 0.5)',
                    'borderColor' => 'rgba(227, 10, 44, 1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
