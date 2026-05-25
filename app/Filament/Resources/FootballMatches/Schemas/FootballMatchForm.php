<?php

namespace App\Filament\Resources\FootballMatches\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class FootballMatchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('season_id')
                    ->required()
                    ->numeric(),
                TextInput::make('matchday')
                    ->numeric(),
                TextInput::make('competition')
                    ->required()
                    ->default('Primera RFEF'),
                TextInput::make('opponent')
                    ->required(),
                TextInput::make('opponent_logo'),
                TextInput::make('venue')
                    ->required(),
                TextInput::make('stadium')
                    ->required()
                    ->default('Nuevo Mirador'),
                DateTimePicker::make('kickoff_at')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('scheduled'),
                TextInput::make('home_score')
                    ->numeric(),
                TextInput::make('away_score')
                    ->numeric(),
                TextInput::make('broadcast'),
                TextInput::make('ticket_external_url')
                    ->url(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
