<?php

namespace App\Filament\Resources\Sponsors\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SponsorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('tier')
                    ->required(),
                TextInput::make('logo'),
                TextInput::make('logo_dark'),
                TextInput::make('url')
                    ->url(),
                Toggle::make('invert_on_dark')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                DatePicker::make('contract_start'),
                DatePicker::make('contract_end'),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('active')
                    ->required(),
            ]);
    }
}
