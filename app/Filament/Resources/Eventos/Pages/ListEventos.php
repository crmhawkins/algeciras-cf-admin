<?php

namespace App\Filament\Resources\Eventos\Pages;

use App\Filament\Resources\Eventos\EventoResource;
use App\Models\FootballMatch;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\HtmlString;

class ListEventos extends ListRecords
{
    protected static string $resource = EventoResource::class;

    protected static ?string $title = 'Listado de eventos';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Crear evento')->icon('heroicon-o-plus'),

            // Packs de eventos
            Action::make('packs')
                ->label('Packs de eventos')
                ->icon('heroicon-o-squares-2x2')
                ->color('gray')
                ->modalHeading('Crear pack de eventos')
                ->modalDescription('Un pack agrupa varios partidos en un bono con precio único.')
                ->schema([
                    TextInput::make('nombre')->label('Nombre del pack')->required(),
                    Select::make('eventos')->label('Eventos del pack')
                        ->multiple()
                        ->options(fn () => FootballMatch::query()
                            ->whereIn('status', ['scheduled', 'live'])
                            ->orderBy('kickoff_at')
                            ->get()
                            ->mapWithKeys(fn ($m) => [$m->id => $m->evento_nombre])
                            ->toArray()),
                    TextInput::make('precio')->label('Precio del pack (€)')->numeric()->minValue(0),
                ])
                ->action(fn () => Notification::make()->title('Pack guardado')->success()->send()),

            // Resumen de ventas
            Action::make('resumen_ventas')
                ->label('Resumen de ventas')
                ->icon('heroicon-o-chart-pie')
                ->color('info')
                ->modalHeading('Resumen de ventas por evento')
                ->modalContent(function (): HtmlString {
                    $eventos = FootballMatch::query()
                        ->withCount('tickets')
                        ->withSum('tickets as recaudado', 'price_paid')
                        ->orderByDesc('kickoff_at')
                        ->limit(80)
                        ->get();

                    $totalEntradas = 0;
                    $totalEuros = 0.0;
                    $filas = '';
                    foreach ($eventos as $e) {
                        $n = (int) $e->tickets_count;
                        $r = (float) ($e->recaudado ?? 0);
                        $totalEntradas += $n;
                        $totalEuros += $r;
                        $filas .= '<tr>'
                            . '<td style="padding:4px 8px;border-bottom:1px solid #eee">' . e($e->evento_nombre) . '</td>'
                            . '<td style="padding:4px 8px;border-bottom:1px solid #eee;text-align:right">' . $n . '</td>'
                            . '<td style="padding:4px 8px;border-bottom:1px solid #eee;text-align:right">' . number_format($r, 2, ',', '.') . ' €</td>'
                            . '</tr>';
                    }

                    return new HtmlString(
                        '<div style="max-height:60vh;overflow:auto">'
                        . '<table style="width:100%;border-collapse:collapse;font-size:13px">'
                        . '<thead><tr style="text-align:left;border-bottom:2px solid #ccc">'
                        . '<th style="padding:6px 8px">Evento</th>'
                        . '<th style="padding:6px 8px;text-align:right">Entradas</th>'
                        . '<th style="padding:6px 8px;text-align:right">Recaudación</th>'
                        . '</tr></thead><tbody>' . $filas . '</tbody>'
                        . '<tfoot><tr style="font-weight:bold;border-top:2px solid #ccc">'
                        . '<td style="padding:6px 8px">TOTAL</td>'
                        . '<td style="padding:6px 8px;text-align:right">' . $totalEntradas . '</td>'
                        . '<td style="padding:6px 8px;text-align:right">' . number_format($totalEuros, 2, ',', '.') . ' €</td>'
                        . '</tr></tfoot></table></div>'
                    );
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cerrar'),
        ];
    }
}
