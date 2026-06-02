<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos personales')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('full_name')->label('Nombre completo'),
                        TextEntry::make('email')->label('Email')->copyable(),
                        TextEntry::make('phone')->label('Teléfono')->placeholder('—'),
                        TextEntry::make('dni')->label('DNI')->placeholder('—'),
                        TextEntry::make('birth_date')->label('Fecha nacimiento')->date('d/m/Y')->placeholder('—'),
                        TextEntry::make('language')->label('Idioma'),
                    ]),

                Section::make('Dirección')
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextEntry::make('address')->label('Dirección')->placeholder('—'),
                        TextEntry::make('city')->label('Ciudad')->placeholder('—'),
                        TextEntry::make('province')->label('Provincia')->placeholder('—'),
                        TextEntry::make('postal_code')->label('CP')->placeholder('—'),
                        TextEntry::make('country')->label('País'),
                    ]),

                Section::make('Estado de socio')
                    ->columns(4)
                    ->schema([
                        IconEntry::make('is_socio')->label('Socio')->boolean(),
                        TextEntry::make('socio_number')->label('Nº socio')->placeholder('—'),
                        TextEntry::make('socio_since')->label('Socio desde')->date('d/m/Y')->placeholder('—'),
                        TextEntry::make('tier_label')->label('Tier')->badge()->color('warning'),
                    ]),

                Section::make('Comunicaciones')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        IconEntry::make('newsletter_optin')->label('Newsletter')->boolean(),
                        IconEntry::make('whatsapp_optin')->label('WhatsApp')->boolean(),
                    ]),
            ]);
    }
}
