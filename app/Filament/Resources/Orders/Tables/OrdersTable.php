<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')
                    ->label('Ref.')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('customer.full_name')
                    ->label('Cliente')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('customer', function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%");
                        })->orWhere('guest_email', 'like', "%{$search}%");
                    })
                    ->default(fn (Order $record) => $record->guest_email ?: '—')
                    ->wrap(),

                TextColumn::make('channel')
                    ->label('Canal')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'web'    => 'info',
                        'pos'    => 'warning',
                        'admin'  => 'gray',
                        default  => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'paid'     => 'success',
                        'pending'  => 'warning',
                        'failed'   => 'danger',
                        'refunded' => 'gray',
                        default    => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('payment_gateway')
                    ->label('Pasarela')
                    ->badge()
                    ->color('gray')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('paid_at')
                    ->label('Pagado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pending'  => 'Pendiente',
                        'paid'     => 'Pagado',
                        'failed'   => 'Fallido',
                        'refunded' => 'Reembolsado',
                    ]),
                SelectFilter::make('channel')
                    ->label('Canal')
                    ->options([
                        'web'   => 'Web',
                        'pos'   => 'POS',
                        'admin' => 'Admin',
                    ]),
                SelectFilter::make('payment_gateway')
                    ->label('Pasarela')
                    ->options([
                        'stripe'  => 'Stripe',
                        'redsys'  => 'Redsys',
                        'paypal'  => 'PayPal',
                        'manual'  => 'Manual',
                    ]),
                Filter::make('paid_at')
                    ->label('Rango fecha pago')
                    ->schema([
                        DatePicker::make('paid_from')->label('Pagado desde'),
                        DatePicker::make('paid_until')->label('Pagado hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['paid_from'] ?? null, fn ($q, $d) => $q->whereDate('paid_at', '>=', $d))
                            ->when($data['paid_until'] ?? null, fn ($q, $d) => $q->whereDate('paid_at', '<=', $d));
                    }),
            ])
            ->recordActions([
                ViewAction::make()->label('Ver'),
                Action::make('refund')
                    ->label('Reembolsar')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar reembolso')
                    ->modalDescription(fn (Order $record) => "El pedido {$record->reference} pasará a estado «reembolsado» y se notificará al cliente.")
                    ->visible(fn (Order $record) => $record->status === 'paid')
                    ->action(function (Order $record): void {
                        $record->update(['status' => 'refunded']);

                        $to = $record->customer?->email ?: $record->guest_email;
                        if ($to) {
                            try {
                                Mail::raw(
                                    "Hola,\n\nTu pedido {$record->reference} por un importe de {$record->total} {$record->currency} ha sido reembolsado.\n\nEl importe aparecerá en tu cuenta en los próximos días hábiles.\n\nUn saludo,\nAlgeciras CF",
                                    function ($message) use ($to, $record) {
                                        $message->to($to)
                                            ->subject("Reembolso del pedido {$record->reference}");
                                    }
                                );
                            } catch (\Throwable $e) {
                                // No bloquear la acción si falla el envío del email
                            }
                        }

                        Notification::make()
                            ->title("Pedido {$record->reference} reembolsado")
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
