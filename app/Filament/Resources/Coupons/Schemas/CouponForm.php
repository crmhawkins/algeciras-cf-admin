<?php

namespace App\Filament\Resources\Coupons\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Código')
                    ->required()
                    ->maxLength(64)
                    ->unique(ignoreRecord: true)
                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                    ->dehydrateStateUsing(fn ($state) => strtoupper(trim((string) $state))),
                TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
                Select::make('type')
                    ->label('Tipo')
                    ->required()
                    ->default('percent')
                    ->options([
                        'percent' => 'Porcentaje (%)',
                        'fixed'   => 'Cantidad fija (€)',
                        'gift'    => 'Regalo',
                    ])
                    ->native(false),
                TextInput::make('value')
                    ->label('Valor')
                    ->numeric()
                    ->default(0)
                    ->helperText('% para percent · € para fixed · 0 para gift'),
                FileUpload::make('image')
                    ->label('Imagen')
                    ->image()
                    ->disk('public')
                    ->directory('coupons'),
                Select::make('target_tier')
                    ->label('Destinatarios')
                    ->required()
                    ->default('all')
                    ->options([
                        'all'          => 'Todos',
                        'abonado'      => 'Abonado',
                        'abonado_vip'  => 'Abonado VIP',
                        'peñista'      => 'Peñista',
                    ])
                    ->native(false),
                TextInput::make('max_uses_per_customer')
                    ->label('Usos máx. por cliente')
                    ->numeric()
                    ->default(1),
                TextInput::make('total_stock')
                    ->label('Stock total')
                    ->numeric()
                    ->helperText('vacío = sin límite'),
                TextInput::make('used_count')
                    ->label('Usados')
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false),
                DatePicker::make('valid_from')
                    ->label('Válido desde'),
                DatePicker::make('valid_until')
                    ->label('Válido hasta'),
                Toggle::make('active')
                    ->label('Activo')
                    ->required()
                    ->default(true),
            ]);
    }
}
