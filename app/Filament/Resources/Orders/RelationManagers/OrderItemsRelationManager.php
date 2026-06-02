<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrderItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Líneas del pedido';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Concepto')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('product_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'ticket'    => 'info',
                        'abono'     => 'warning',
                        'merch'     => 'success',
                        'donacion'  => 'gray',
                        default     => 'gray',
                    }),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->placeholder('—'),
                TextColumn::make('qty')
                    ->label('Cant.')
                    ->numeric(),
                TextColumn::make('unit_price')
                    ->label('Unitario')
                    ->money('EUR'),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('EUR'),
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
