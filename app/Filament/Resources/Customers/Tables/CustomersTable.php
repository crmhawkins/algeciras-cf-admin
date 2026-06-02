<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\Coupon;
use App\Models\CustomerCoupon;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $q) => $q
                ->withCount([
                    'orders',
                    'tickets as abonos_activos_count' => fn ($sub) => $sub
                        ->whereHas('product', fn ($p) => $p->where('type', 'abono'))
                        ->whereIn('status', ['issued', 'valid']),
                ]))
            ->columns([
                TextColumn::make('full_name')
                    ->label('Nombre')
                    ->searchable(query: fn (Builder $q, string $s) => $q
                        ->where('first_name', 'like', "%{$s}%")
                        ->orWhere('last_name', 'like', "%{$s}%"))
                    ->sortable(query: fn (Builder $q, string $dir) => $q
                        ->orderBy('first_name', $dir)
                        ->orderBy('last_name', $dir)),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),

                TextColumn::make('dni')
                    ->label('DNI')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->placeholder('—'),

                IconColumn::make('is_socio')
                    ->label('Socio')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('orders_count')
                    ->label('# Pedidos')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('abonos_activos_count')
                    ->label('# Abonos activos')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('warning'),

                TextColumn::make('city')
                    ->label('Ciudad')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_socio')
                    ->label('Socio'),
                TernaryFilter::make('newsletter_optin')
                    ->label('Newsletter'),
                TernaryFilter::make('whatsapp_optin')
                    ->label('WhatsApp'),
            ])
            ->recordActions([
                ViewAction::make()->label('Ver'),
                EditAction::make()->label('Editar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('asignar_cupon')
                        ->label('Asignar cupón')
                        ->icon('heroicon-o-ticket')
                        ->color('success')
                        ->schema([
                            Select::make('coupon_id')
                                ->label('Cupón')
                                ->required()
                                ->searchable()
                                ->options(fn () => Coupon::active()->orderBy('code')->get()
                                    ->mapWithKeys(fn ($c) => [$c->id => $c->code . ' — ' . $c->title])
                                    ->toArray()),
                        ])
                        ->action(function (array $data, Collection $records): void {
                            $couponId = (int) ($data['coupon_id'] ?? 0);
                            if (! $couponId) {
                                return;
                            }
                            $asignados = 0;
                            foreach ($records as $customer) {
                                $cc = CustomerCoupon::firstOrCreate(
                                    [
                                        'customer_id' => $customer->id,
                                        'coupon_id'   => $couponId,
                                    ],
                                    [
                                        'status' => 'available',
                                    ]
                                );
                                if ($cc->wasRecentlyCreated) {
                                    $asignados++;
                                }
                            }
                            Notification::make()
                                ->title("{$asignados} cupones asignados a {$records->count()} socios")
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
