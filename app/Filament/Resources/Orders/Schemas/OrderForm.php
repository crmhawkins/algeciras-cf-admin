<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('reference')
                    ->required(),
                TextInput::make('customer_id')
                    ->numeric(),
                TextInput::make('guest_email')
                    ->email(),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('channel')
                    ->required()
                    ->default('web'),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('vat')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('shipping_cost')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->prefix('$'),
                TextInput::make('total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('currency')
                    ->required()
                    ->default('EUR'),
                TextInput::make('payment_gateway'),
                TextInput::make('payment_intent_id'),
                Textarea::make('shipping_address')
                    ->columnSpanFull(),
                Textarea::make('billing_address')
                    ->columnSpanFull(),
                TextInput::make('tracking_carrier'),
                TextInput::make('tracking_number'),
                DateTimePicker::make('paid_at'),
                DateTimePicker::make('fulfilled_at'),
                DateTimePicker::make('cancelled_at'),
                Textarea::make('admin_notes')
                    ->columnSpanFull(),
            ]);
    }
}
