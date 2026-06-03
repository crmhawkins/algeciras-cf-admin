<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * KPI cards — ingresos del club.
 *
 * Cuenta solo pedidos en estado 'paid' (excluye pending / cancelled).
 * Aparece como primera fila del Dashboard de admin.
 */
class VentasStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = -100;

    protected function getStats(): array
    {
        $now = now();

        // Ingresos por ventana temporal — sólo pedidos pagados.
        $hoy = (float) Order::whereIn('status', ['paid', 'fulfilled'])
            ->whereDate('created_at', $now->toDateString())
            ->sum('total');

        $semana = (float) Order::whereIn('status', ['paid', 'fulfilled'])
            ->where('created_at', '>=', $now->copy()->startOfWeek())
            ->sum('total');

        $mes = (float) Order::whereIn('status', ['paid', 'fulfilled'])
            ->where('created_at', '>=', $now->copy()->startOfMonth())
            ->sum('total');

        $totalPedidosMes = (int) Order::whereIn('status', ['paid', 'fulfilled'])
            ->where('created_at', '>=', $now->copy()->startOfMonth())
            ->count();

        // Variación respecto a la semana pasada
        $semanaPasada = (float) Order::whereIn('status', ['paid', 'fulfilled'])
            ->whereBetween('created_at', [
                $now->copy()->subWeek()->startOfWeek(),
                $now->copy()->subWeek()->endOfWeek(),
            ])
            ->sum('total');
        $varSemana = $semanaPasada > 0
            ? round((($semana - $semanaPasada) / $semanaPasada) * 100, 1)
            : null;

        return [
            Stat::make('Ingresos hoy', '€ ' . number_format($hoy, 2, ',', '.'))
                ->description($totalPedidosMes . ' pedidos pagados este mes')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('success'),

            Stat::make('Ingresos esta semana', '€ ' . number_format($semana, 2, ',', '.'))
                ->description($varSemana !== null
                    ? ($varSemana >= 0 ? "+{$varSemana}% vs semana anterior" : "{$varSemana}% vs semana anterior")
                    : 'sin datos comparativos')
                ->descriptionIcon($varSemana !== null && $varSemana >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($varSemana !== null && $varSemana >= 0 ? 'success' : 'warning'),

            Stat::make('Ingresos mes', '€ ' . number_format($mes, 2, ',', '.'))
                ->description($now->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
        ];
    }
}
