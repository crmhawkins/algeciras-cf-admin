<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'tickets';

    protected static ?string $title = 'Entradas y abonos';

    protected static ?string $recordTitleAttribute = 'uuid';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('product.name')->label('Producto')->wrap()->searchable(),
                TextColumn::make('match.opponent')->label('Partido')->placeholder('—'),
                TextColumn::make('zone.name')->label('Zona')->placeholder('—'),
                TextColumn::make('holder_name')->label('Titular')->placeholder('—'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'issued' => 'success',
                        'valid'  => 'success',
                        'used'   => 'gray',
                        'voided' => 'danger',
                        default  => 'gray',
                    }),
                TextColumn::make('created_at')->label('Creado')->dateTime('d/m/Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'issued' => 'Emitido',
                        'valid'  => 'Válido',
                        'used'   => 'Usado',
                        'voided' => 'Anulado',
                    ]),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
