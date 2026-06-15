<?php

namespace App\Filament\Resources\Abonados\Tables;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use App\Models\Sancion;
use App\Models\Season;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Listado de abonados con el menú OPCIONES calcado del proveedor.
 *
 * REGLAS CERO: "Enviar abono" va CAPADO por kill switch (nunca envía).
 * "Realizar devolución" queda montado pero SIN reembolso real Redsys.
 */
class AbonadosTable
{
    public static function configure(Table $table): Table
    {
        $currentSeasonId = Season::current()?->id ?? Season::max('id');

        return $table
            ->columns([
                IconColumn::make('estado')
                    ->label('Estado')
                    ->getStateUsing(fn (Customer $r): bool => $r->tickets()
                        ->where('season_id', $currentSeasonId)
                        ->where('status', '!=', 'cancelled')
                        ->exists())
                    ->boolean()
                    ->trueIcon('heroicon-s-check-circle')
                    ->falseIcon('heroicon-s-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                TextColumn::make('socio_number')
                    ->label('Nº de abonado')
                    ->sortable()
                    ->searchable()
                    ->alignCenter(),

                TextColumn::make('origen')
                    ->label('Origen')
                    ->getStateUsing(fn (Customer $r) => is_array($r->notes) ? ($r->notes['origen'] ?? null) : null)
                    ->placeholder('—'),

                TextColumn::make('dni')
                    ->label('DNI')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('full_name')
                    ->label('Nombre')
                    ->searchable(query: fn (Builder $q, string $s) => $q
                        ->where('first_name', 'like', "%{$s}%")
                        ->orWhere('last_name', 'like', "%{$s}%")),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->placeholder('—')
                    ->copyable(),
            ])
            ->defaultSort('socio_number', 'asc')
            ->filters([
                SelectFilter::make('estado_abono')
                    ->label('Estado')
                    ->options([
                        'activo'   => 'Renovado / activo',
                        'inactivo' => 'No renovado',
                    ])
                    ->query(function (Builder $q, array $data) use ($currentSeasonId) {
                        $v = $data['value'] ?? null;
                        if ($v === 'activo') {
                            $q->whereHas('tickets', fn ($t) => $t->where('season_id', $currentSeasonId)->where('status', '!=', 'cancelled'));
                        } elseif ($v === 'inactivo') {
                            $q->whereDoesntHave('tickets', fn ($t) => $t->where('season_id', $currentSeasonId)->where('status', '!=', 'cancelled'));
                        }
                    }),

                SelectFilter::make('tipo_abono')
                    ->label('Tipo de abono')
                    ->options(fn () => Customer::query()
                        ->whereNotNull('socio_number')
                        ->get()
                        ->map(fn ($c) => is_array($c->notes) ? ($c->notes['tipo_precio'] ?? null) : null)
                        ->filter()
                        ->unique()
                        ->sort()
                        ->mapWithKeys(fn ($t) => [$t => $t])
                        ->toArray())
                    ->query(function (Builder $q, array $data) {
                        if (!empty($data['value'])) {
                            $q->where('notes->tipo_precio', $data['value']);
                        }
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()->label('Ver abonado'),

                    Action::make('ver_usuario')
                        ->label('Ver usuario')
                        ->icon('heroicon-o-user')
                        ->url(fn (Customer $r) => CustomerResource::getUrl('view', ['record' => $r]))
                        ->openUrlInNewTab(),

                    Action::make('enviar_abono')
                        ->label('Enviar abono')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Enviar abono por email')
                        ->modalDescription('El envío de correos está DESACTIVADO por el kill switch del sistema. No se enviará nada a ningún abonado.')
                        ->action(fn () => Notification::make()
                            ->title('Envío bloqueado por kill switch')
                            ->body('No se ha enviado ningún correo. El envío a clientes está deshabilitado.')
                            ->warning()
                            ->send()),

                    Action::make('descargar_abono')
                        ->label('Descargar abono')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->url(fn (Customer $r) => ($t = $r->currentAbonoTicket()) ? route('admin.carnet', $t->id) : null)
                        ->openUrlInNewTab()
                        ->visible(fn (Customer $r) => $r->currentAbonoTicket() !== null),

                    EditAction::make()->label('Editar abonado'),

                    Action::make('imprimir_abono')
                        ->label('Imprimir abono')
                        ->icon('heroicon-o-printer')
                        ->url(fn (Customer $r) => ($t = $r->currentAbonoTicket()) ? route('admin.carnet', $t->id) : null)
                        ->openUrlInNewTab()
                        ->visible(fn (Customer $r) => $r->currentAbonoTicket() !== null),

                    Action::make('imprimir_recibo')
                        ->label('Imprimir recibo')
                        ->icon('heroicon-o-document-text')
                        ->url(fn (Customer $r) => ($t = $r->currentAbonoTicket()) ? route('admin.recibo', $t->id) : null)
                        ->openUrlInNewTab()
                        ->visible(fn (Customer $r) => $r->currentAbonoTicket() !== null),

                    Action::make('reimpresion')
                        ->label('Reimpresión perdida')
                        ->icon('heroicon-o-arrow-path')
                        ->url(fn (Customer $r) => ($t = $r->currentAbonoTicket()) ? route('admin.carnet', $t->id) : null)
                        ->openUrlInNewTab()
                        ->visible(fn (Customer $r) => $r->currentAbonoTicket() !== null),

                    Action::make('sanciones')
                        ->label('Sanciones')
                        ->icon('heroicon-o-exclamation-triangle')
                        ->color('danger')
                        ->modalHeading(fn (Customer $r) => 'Sanciones de ' . $r->full_name)
                        ->modalDescription(fn (Customer $r) => 'Sanciones registradas: ' . $r->sanciones()->count())
                        ->schema([
                            TextInput::make('motivo')->label('Motivo')->required()->maxLength(255),
                            TextInput::make('importe')->label('Importe (€)')->numeric()->minValue(0),
                            DatePicker::make('fecha')->label('Fecha')->default(now()),
                        ])
                        ->action(function (array $data, Customer $r) {
                            Sancion::create([
                                'customer_id' => $r->id,
                                'motivo'      => $data['motivo'],
                                'importe'     => $data['importe'] ?? null,
                                'fecha'       => $data['fecha'] ?? now(),
                                'activa'      => true,
                            ]);
                            Notification::make()->title('Sanción registrada')->success()->send();
                        }),

                    Action::make('liberar_asiento')
                        ->label('Liberar asiento')
                        ->icon('heroicon-o-lock-open')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Liberar asiento')
                        ->modalDescription('Liberará la butaca del abono de esta temporada (quedará disponible) y el abonado pasará a NO renovado. No afecta al histórico.')
                        ->action(function (Customer $r) {
                            $t = $r->currentAbonoTicket();
                            if (! $t) {
                                Notification::make()->title('Este abonado no tiene asiento que liberar')->warning()->send();
                                return;
                            }
                            if ($t->seat) {
                                $t->seat->forceFill(['status' => 'free'])->save();
                            }
                            $t->forceFill(['status' => 'cancelled'])->save();
                            Notification::make()->title('Asiento liberado')->success()->send();
                        }),

                    Action::make('devolucion')
                        ->label('Realizar devolución')
                        ->icon('heroicon-o-receipt-refund')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Realizar devolución')
                        ->modalDescription('Esta acción NO ejecuta reembolso real en Redsys (deshabilitado hasta autorización). Solo deja constancia de la solicitud.')
                        ->action(fn (Customer $r) => Notification::make()
                            ->title('Devolución registrada (sin reembolso real)')
                            ->body('El reembolso por Redsys está deshabilitado en esta réplica. No se ha movido dinero.')
                            ->warning()
                            ->send()),

                    DeleteAction::make()->label('Borrar abonado'),
                ])
                    ->label('Opciones')
                    ->button()
                    ->color('gray'),
            ]);
    }
}
