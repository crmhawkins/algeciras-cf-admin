<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Ticket;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

/**
 * KPI cards — base de datos de clientes/socios.
 */
class ClientesStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -90;

    protected function getStats(): array
    {
        $now = now();

        $totalClientes = (int) Customer::count();

        $nuevosHoy = (int) Customer::whereDate('created_at', $now->toDateString())->count();

        $nuevosSemana = (int) Customer::where('created_at', '>=', $now->copy()->startOfWeek())->count();

        // Socios activos = customers únicos con ticket emitido cuyo Product
        // es de tipo 'abono'. El "tipo" vive en products.type, no en tickets.
        $sociosActivos = (int) DB::table('tickets')
            ->join('products', 'products.id', '=', 'tickets.product_id')
            ->where('products.type', 'abono')
            ->where('tickets.status', 'issued')
            ->distinct('tickets.customer_id')
            ->count('tickets.customer_id');

        return [
            Stat::make('Clientes registrados', number_format($totalClientes, 0, ',', '.'))
                ->description($nuevosHoy . ' nuevos hoy · ' . $nuevosSemana . ' esta semana')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary'),

            Stat::make('Socios con abono', number_format($sociosActivos, 0, ',', '.'))
                ->description('Tickets de abono emitidos')
                ->descriptionIcon('heroicon-m-identification')
                ->color('success'),

            Stat::make('% conversión a socio', $totalClientes > 0
                ? round(($sociosActivos / $totalClientes) * 100, 1) . '%'
                : '0%')
                ->description('Sobre el total de clientes')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}
