<?php

namespace App\Filament\Resources\Attendances\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AttendanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Acceso')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('id')->label('ID'),
                        TextEntry::make('scanned_at')->label('Escaneado el')->dateTime('d/m/Y H:i:s'),
                        TextEntry::make('gate_id')->label('Puerta')->placeholder('—'),
                    ]),

                Section::make('Partido')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('match.matchday')->label('Jornada')->placeholder('—'),
                        TextEntry::make('match.opponent')->label('Rival')->placeholder('—'),
                        TextEntry::make('match.kickoff_at')->label('Saque')->dateTime('d/m/Y H:i')->placeholder('—'),
                    ]),

                Section::make('Ticket')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('ticket.id')->label('Ticket ID')->placeholder('—'),
                        TextEntry::make('ticket.uuid')->label('UUID')->copyable()->placeholder('—'),
                        TextEntry::make('ticket.product.name')
                            ->label('Producto')
                            ->badge()
                            ->color(fn ($record) => match ($record?->ticket?->product?->type) {
                                'abono'   => 'success',
                                'entrada' => 'warning',
                                default   => 'gray',
                            })
                            ->placeholder('—'),
                        TextEntry::make('ticket.zone.name')->label('Zona')->placeholder('—'),
                        TextEntry::make('ticket.customer.full_name')->label('Cliente')->placeholder('—'),
                        TextEntry::make('ticket.holder_dni')->label('DNI titular')->placeholder('—'),
                    ]),

                Section::make('Operador')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('scannedBy.name')->label('Escaneado por')->placeholder('—'),
                        TextEntry::make('scannedBy.email')->label('Email operador')->placeholder('—'),
                    ]),

                Section::make('Meta')
                    ->schema([
                        TextEntry::make('meta')
                            ->label('Metadatos')
                            ->placeholder('—')
                            ->formatStateUsing(fn ($state) => is_array($state)
                                ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                                : (string) ($state ?? '—'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
