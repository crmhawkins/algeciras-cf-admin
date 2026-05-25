<?php

namespace App\Filament\Resources\Tickets\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_item_id')
                    ->required()
                    ->numeric(),
                TextInput::make('customer_id')
                    ->numeric(),
                TextInput::make('product_id')
                    ->required()
                    ->numeric(),
                TextInput::make('match_id')
                    ->numeric(),
                TextInput::make('season_id')
                    ->numeric(),
                TextInput::make('zone_id')
                    ->numeric(),
                TextInput::make('uuid')
                    ->label('UUID')
                    ->required(),
                FileUpload::make('qr_image_path')
                    ->image(),
                TextInput::make('status')
                    ->required()
                    ->default('issued'),
                TextInput::make('holder_name'),
                TextInput::make('holder_dni'),
                DateTimePicker::make('valid_from'),
                DateTimePicker::make('valid_until'),
                DateTimePicker::make('used_at'),
                TextInput::make('used_by_admin_id')
                    ->numeric(),
                TextInput::make('used_gate'),
            ]);
    }
}
