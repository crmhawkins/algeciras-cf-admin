<?php

namespace App\Filament\Resources\Players\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PlayerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('dorsal')
                    ->numeric(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('display_name')
                    ->required(),
                TextInput::make('full_name'),
                TextInput::make('position')
                    ->required(),
                TextInput::make('photo'),
                TextInput::make('photo_action'),
                DatePicker::make('birth_date'),
                TextInput::make('birth_place'),
                TextInput::make('nationality')
                    ->required()
                    ->default('España'),
                TextInput::make('height_cm')
                    ->numeric(),
                TextInput::make('weight_kg')
                    ->numeric(),
                TextInput::make('preferred_foot'),
                Textarea::make('bio')
                    ->columnSpanFull(),
                TextInput::make('instagram'),
                TextInput::make('x_handle'),
                DatePicker::make('joined_at'),
                DatePicker::make('contract_end'),
                Toggle::make('active')
                    ->required(),
                Toggle::make('captain')
                    ->required(),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
