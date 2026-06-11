<?php

namespace App\Filament\Resources\AbonoPrecios\Schemas;

use App\Models\AbonoPrecio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AbonoPrecioForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('descripcion')
                    ->label('Descripción')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Select::make('zona')
                    ->label('Zona del estadio')
                    ->required()
                    ->options(AbonoPrecio::ZONAS)
                    ->native(false),
                Select::make('modalidad')
                    ->label('Modalidad')
                    ->required()
                    ->default('nueva')
                    ->options(AbonoPrecio::MODALIDADES)
                    ->native(false),
                Toggle::make('es_infantil')
                    ->label('Infantil')
                    ->default(false),
                TextInput::make('precio')
                    ->label('Precio (€)')
                    ->required()
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0),
                TextInput::make('edad_min')
                    ->label('Edad mínima')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(120)
                    ->placeholder('—'),
                TextInput::make('edad_max')
                    ->label('Edad máxima')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(120)
                    ->placeholder('—'),
                Toggle::make('renovacion')
                    ->label('Renovación')
                    ->helperText('Marca este tipo como abono de renovación.')
                    ->default(false),
                Toggle::make('pago_plazos')
                    ->label('Permite pago a plazos')
                    ->default(false),
                TextInput::make('stock')
                    ->label('Stock')
                    ->numeric()
                    ->helperText('Vacío = sin límite'),
                TextInput::make('orden')
                    ->label('Orden')
                    ->numeric()
                    ->default(0),
                Toggle::make('activo')
                    ->label('Activo')
                    ->default(true),
            ]);
    }
}
