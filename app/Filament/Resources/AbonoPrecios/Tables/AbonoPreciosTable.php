<?php

namespace App\Filament\Resources\AbonoPrecios\Tables;

use App\Models\AbonoPrecio;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AbonoPreciosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('orden')
            ->columns([
                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->weight('bold')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('zona')
                    ->label('Zona')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state) => AbonoPrecio::ZONAS[$state] ?? $state)
                    ->sortable(),
                TextColumn::make('modalidad')
                    ->label('Modalidad')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'renovacion' ? 'info' : 'success')
                    ->formatStateUsing(fn (?string $state) => AbonoPrecio::MODALIDADES[$state] ?? $state)
                    ->sortable(),
                IconColumn::make('es_infantil')
                    ->label('Infantil')
                    ->boolean(),
                TextColumn::make('precio')
                    ->label('Precio')
                    ->money('EUR')
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('rango_edad')
                    ->label('Edad')
                    ->getStateUsing(fn ($record) => $record->rango_edad)
                    ->placeholder('—'),
                IconColumn::make('renovacion')
                    ->label('Renovación')
                    ->boolean()
                    ->sortable(),
                IconColumn::make('pago_plazos')
                    ->label('Pago a plazos')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('stock')
                    ->label('Stock')
                    ->placeholder('∞')
                    ->toggleable(),
                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('zona')
                    ->label('Zona')
                    ->options(AbonoPrecio::ZONAS),
                SelectFilter::make('modalidad')
                    ->label('Modalidad')
                    ->options(AbonoPrecio::MODALIDADES),
                TernaryFilter::make('es_infantil')
                    ->label('Infantil'),
                TernaryFilter::make('renovacion')
                    ->label('Renovación'),
                TernaryFilter::make('activo')
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
