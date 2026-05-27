<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Models\Coupon;
use App\Models\CustomerCoupon;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('first_name')
                    ->searchable(),
                TextColumn::make('last_name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('dni')
                    ->searchable(),
                TextColumn::make('birth_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('address')
                    ->searchable(),
                TextColumn::make('city')
                    ->searchable(),
                TextColumn::make('province')
                    ->searchable(),
                TextColumn::make('postal_code')
                    ->searchable(),
                TextColumn::make('country')
                    ->searchable(),
                IconColumn::make('is_socio')
                    ->boolean(),
                TextColumn::make('socio_number')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('socio_since')
                    ->date()
                    ->sortable(),
                TextColumn::make('language')
                    ->searchable(),
                IconColumn::make('newsletter_optin')
                    ->boolean(),
                IconColumn::make('whatsapp_optin')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
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
