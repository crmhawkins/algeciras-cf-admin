<?php

namespace App\Filament\Resources\Eventos\Tables;

use App\Models\FootballMatch;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

/**
 * Listado de eventos con botones por evento (Compañía · Informe · Sesiones ·
 * Opciones) calcados del proveedor.
 */
class EventosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->getStateUsing(fn (FootballMatch $r) => match ($r->status) {
                        'scheduled' => 'Programado',
                        'live'      => 'En juego',
                        'finished'  => 'Finalizado',
                        'postponed' => 'Aplazado',
                        default     => $r->status,
                    })
                    ->color(fn (FootballMatch $r) => match ($r->status) {
                        'scheduled' => 'info',
                        'live'      => 'success',
                        'finished'  => 'gray',
                        'postponed' => 'warning',
                        default     => 'gray',
                    }),

                TextColumn::make('evento_nombre')
                    ->label('Evento')
                    ->getStateUsing(fn (FootballMatch $r) => $r->evento_nombre)
                    ->searchable(query: fn (Builder $q, string $s) => $q->where('opponent', 'like', "%{$s}%"))
                    ->weight('bold'),

                TextColumn::make('stadium')->label('Ubicación')->placeholder('—'),

                TextColumn::make('kickoff_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('kickoff_at', 'desc')
            ->filters([
                SelectFilter::make('segmento')
                    ->label('Eventos')
                    ->options([
                        'actuales' => 'Eventos actuales',
                        'pasados'  => 'Eventos pasados',
                    ])
                    ->query(function (Builder $q, array $data) {
                        $v = $data['value'] ?? null;
                        if ($v === 'actuales') {
                            $q->whereIn('status', ['scheduled', 'live', 'postponed']);
                        } elseif ($v === 'pasados') {
                            $q->where('status', 'finished');
                        }
                    }),

                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'scheduled' => 'Programado',
                        'live'      => 'En juego',
                        'finished'  => 'Finalizado',
                        'postponed' => 'Aplazado',
                    ]),
            ])
            ->recordActions([
                // Compañía
                Action::make('compania')
                    ->label('Compañía')
                    ->icon('heroicon-o-user-group')
                    ->color('gray')
                    ->modalHeading(fn (FootballMatch $r) => 'Compañía — ' . $r->evento_nombre)
                    ->modalDescription(fn (FootballMatch $r) => new HtmlString(
                        '<b>Rival:</b> ' . e($r->opponent) . '<br>' .
                        '<b>Competición:</b> ' . e($r->competition) . '<br>' .
                        '<b>Condición:</b> ' . ($r->venue === 'home' ? 'Local' : 'Visitante') . '<br>' .
                        '<b>Estadio:</b> ' . e($r->stadium)
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),

                // Informe (ventas)
                Action::make('informe')
                    ->label('Informe')
                    ->icon('heroicon-o-chart-bar')
                    ->color('info')
                    ->modalHeading(fn (FootballMatch $r) => 'Informe de ventas — ' . $r->evento_nombre)
                    ->modalDescription(function (FootballMatch $r) {
                        $vendidas = $r->tickets()->count();
                        $recaudado = (float) $r->tickets()->sum('price_paid');
                        return new HtmlString(
                            '<b>Entradas vendidas:</b> ' . $vendidas . '<br>' .
                            '<b>Recaudación:</b> ' . number_format($recaudado, 2, ',', '.') . ' €'
                        );
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),

                // Sesiones
                Action::make('sesiones')
                    ->label('Sesiones')
                    ->icon('heroicon-o-calendar-days')
                    ->color('gray')
                    ->modalHeading(fn (FootballMatch $r) => 'Sesiones — ' . $r->evento_nombre)
                    ->modalDescription(fn (FootballMatch $r) => new HtmlString(
                        'Sesión única: <b>' . optional($r->kickoff_at)->format('d/m/Y H:i') . '</b>'
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),

                // Opciones (Editar / Borrar / Acreditaciones / Campos personalizados)
                ActionGroup::make([
                    EditAction::make()->label('Editar'),
                    DeleteAction::make()->label('Borrar'),
                    Action::make('acreditaciones')
                        ->label('Acreditaciones')
                        ->icon('heroicon-o-identification')
                        ->modalHeading(fn (FootballMatch $r) => 'Acreditaciones — ' . $r->evento_nombre)
                        ->schema([
                            TextInput::make('nombre')->label('Nombre acreditado')->required(),
                            TextInput::make('entidad')->label('Medio / entidad'),
                        ])
                        ->action(fn () => Notification::make()->title('Acreditación registrada')->success()->send()),
                    Action::make('campos_personalizados')
                        ->label('Campos personalizados')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->modalHeading(fn (FootballMatch $r) => 'Campos personalizados — ' . $r->evento_nombre)
                        ->schema([
                            TextInput::make('campo')->label('Nombre del campo'),
                            TextInput::make('valor')->label('Valor'),
                        ])
                        ->action(fn () => Notification::make()->title('Campo guardado')->success()->send()),
                ])
                    ->label('Opciones')
                    ->button()
                    ->color('gray'),
            ]);
    }
}
