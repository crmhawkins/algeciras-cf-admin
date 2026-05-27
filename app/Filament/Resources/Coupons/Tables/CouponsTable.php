<?php

namespace App\Filament\Resources\Coupons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->badge()
                    ->color('danger')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'percent' => 'warning',
                        'fixed'   => 'info',
                        'gift'    => 'success',
                        default   => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('display_value')
                    ->label('Valor')
                    ->getStateUsing(fn ($record) => $record?->display_value ?? $record?->value),
                TextColumn::make('target_tier')
                    ->label('Destinatarios')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'all'         => 'gray',
                        'abonado'     => 'info',
                        'abonado_vip' => 'warning',
                        'peñista'     => 'success',
                        default       => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('usage')
                    ->label('Usados / Stock')
                    ->getStateUsing(function ($record) {
                        $stock = $record->total_stock !== null ? $record->total_stock : '∞';
                        return ($record->used_count ?? 0) . ' / ' . $stock;
                    }),
                TextColumn::make('valid_until')
                    ->label('Caduca')
                    ->date()
                    ->sortable()
                    ->color(function ($record) {
                        if ($record->valid_until && $record->valid_until->isPast()) {
                            return 'danger';
                        }
                        return null;
                    }),
                IconColumn::make('active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
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
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'percent' => 'Porcentaje',
                        'fixed'   => 'Cantidad fija',
                        'gift'    => 'Regalo',
                    ]),
                SelectFilter::make('target_tier')
                    ->label('Destinatarios')
                    ->options([
                        'all'         => 'Todos',
                        'abonado'     => 'Abonado',
                        'abonado_vip' => 'Abonado VIP',
                        'peñista'     => 'Peñista',
                    ]),
                TernaryFilter::make('active')
                    ->label('Activo'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
