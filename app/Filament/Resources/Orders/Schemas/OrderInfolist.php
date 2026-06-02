<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Pedido')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('reference')->label('Referencia')->copyable(),
                        TextEntry::make('status')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (?string $state) => match ($state) {
                                'paid'     => 'success',
                                'pending'  => 'warning',
                                'failed'   => 'danger',
                                'refunded' => 'gray',
                                default    => 'gray',
                            }),
                        TextEntry::make('channel')->label('Canal')->badge(),
                        TextEntry::make('created_at')->label('Creado')->dateTime('d/m/Y H:i'),
                        TextEntry::make('paid_at')->label('Pagado')->dateTime('d/m/Y H:i')->placeholder('—'),
                        TextEntry::make('cancelled_at')->label('Cancelado')->dateTime('d/m/Y H:i')->placeholder('—'),
                    ]),

                Section::make('Cliente')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('customer.full_name')
                            ->label('Nombre')
                            ->placeholder('Invitado'),
                        TextEntry::make('customer.email')
                            ->label('Email')
                            ->placeholder(fn ($record) => $record->guest_email ?: '—')
                            ->copyable(),
                        TextEntry::make('customer.phone')->label('Teléfono')->placeholder('—'),
                    ]),

                Section::make('Importes')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('subtotal')->label('Subtotal')->money('EUR'),
                        TextEntry::make('vat')->label('IVA')->money('EUR'),
                        TextEntry::make('shipping_cost')->label('Envío')->money('EUR'),
                        TextEntry::make('total')->label('Total')->money('EUR')->weight('bold'),
                        TextEntry::make('currency')->label('Moneda'),
                        TextEntry::make('payment_gateway')->label('Pasarela')->badge()->placeholder('—'),
                        TextEntry::make('payment_intent_id')->label('Payment intent')->copyable()->placeholder('—'),
                    ]),

                Section::make('Líneas')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->columns(6)
                            ->schema([
                                TextEntry::make('name')->label('Concepto')->columnSpan(2),
                                TextEntry::make('product_type')->label('Tipo')->badge(),
                                TextEntry::make('qty')->label('Cant.'),
                                TextEntry::make('unit_price')->label('Unit.')->money('EUR'),
                                TextEntry::make('total')->label('Total')->money('EUR'),
                            ]),
                    ]),

                Section::make('Envío y notas')
                    ->columns(2)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        TextEntry::make('tracking_carrier')->label('Transportista')->placeholder('—'),
                        TextEntry::make('tracking_number')->label('Nº seguimiento')->placeholder('—'),
                        TextEntry::make('admin_notes')->label('Notas internas')->columnSpanFull()->placeholder('—'),
                    ]),
            ]);
    }
}
