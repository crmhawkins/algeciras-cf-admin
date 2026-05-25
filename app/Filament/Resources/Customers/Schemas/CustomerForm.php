<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->numeric(),
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('dni'),
                DatePicker::make('birth_date'),
                TextInput::make('address'),
                TextInput::make('city'),
                TextInput::make('province'),
                TextInput::make('postal_code'),
                TextInput::make('country')
                    ->required()
                    ->default('España'),
                Toggle::make('is_socio')
                    ->required(),
                TextInput::make('socio_number')
                    ->numeric(),
                DatePicker::make('socio_since'),
                TextInput::make('language')
                    ->required()
                    ->default('es'),
                Toggle::make('newsletter_optin')
                    ->required(),
                Toggle::make('whatsapp_optin')
                    ->required(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
