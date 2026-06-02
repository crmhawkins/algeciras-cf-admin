<?php

namespace App\Filament\Resources\Tickets\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Ticket')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('id')->label('ID'),
                        TextEntry::make('uuid')->label('UUID')->copyable(),
                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (?string $state) => match ($state) {
                                'issued' => 'success',
                                'valid'  => 'success',
                                'used'   => 'gray',
                                'voided' => 'danger',
                                default  => 'gray',
                            }),
                        TextEntry::make('product.name')->label('Producto')->placeholder('—'),
                        TextEntry::make('zone.name')->label('Zona')->placeholder('—'),
                        TextEntry::make('season.name')->label('Temporada')->placeholder('—'),
                    ]),

                Section::make('Partido')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('match.opponent')->label('Rival')->placeholder('—'),
                        TextEntry::make('match.kickoff_at')->label('Saque')->dateTime('d/m/Y H:i')->placeholder('—'),
                        TextEntry::make('match.stadium')->label('Estadio')->placeholder('—'),
                    ]),

                Section::make('Titular')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('customer.full_name')->label('Cliente')->placeholder('—'),
                        TextEntry::make('holder_name')->label('Nombre titular')->placeholder('—'),
                        TextEntry::make('holder_dni')->label('DNI titular')->placeholder('—'),
                    ]),

                Section::make('Validez y uso')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('valid_from')->label('Válido desde')->dateTime('d/m/Y H:i')->placeholder('—'),
                        TextEntry::make('valid_until')->label('Válido hasta')->dateTime('d/m/Y H:i')->placeholder('—'),
                        TextEntry::make('used_at')->label('Usado el')->dateTime('d/m/Y H:i')->placeholder('—'),
                        TextEntry::make('used_gate')->label('Puerta')->placeholder('—'),
                    ]),

                Section::make('QR')
                    ->schema([
                        ImageEntry::make('qr_image_path')
                            ->label('')
                            ->height(240)
                            ->getStateUsing(fn ($record) => $record?->qr_image_path ? asset($record->qr_image_path) : null),
                    ]),
            ]);
    }
}
