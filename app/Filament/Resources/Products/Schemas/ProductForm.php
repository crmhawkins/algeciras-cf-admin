<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sku')
                    ->label('SKU')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('type')
                    ->required(),
                TextInput::make('category_id')
                    ->numeric(),
                Textarea::make('name')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Textarea::make('short_description')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('compare_at_price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('vat_rate')
                    ->required()
                    ->numeric()
                    ->default(21),
                FileUpload::make('image')
                    ->image(),
                Textarea::make('gallery')
                    ->columnSpanFull(),
                Toggle::make('active')
                    ->required(),
                Toggle::make('featured')
                    ->required(),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('has_variants')
                    ->required(),
                Toggle::make('ship_required')
                    ->required(),
                TextInput::make('stock')
                    ->numeric(),
                TextInput::make('weight_kg')
                    ->numeric(),
                TextInput::make('match_id')
                    ->numeric(),
                TextInput::make('season_id')
                    ->numeric(),
                TextInput::make('zone_id')
                    ->numeric(),
                TextInput::make('capacity')
                    ->numeric(),
                TextInput::make('sold')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('sale_starts_at'),
                DateTimePicker::make('sale_ends_at'),
                Toggle::make('socios_only')
                    ->required(),
            ]);
    }
}
