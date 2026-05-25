<?php

namespace App\Filament\Resources\FootballMatches\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FootballMatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('season_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('matchday')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('competition')
                    ->searchable(),
                TextColumn::make('opponent')
                    ->searchable(),
                TextColumn::make('opponent_logo')
                    ->searchable(),
                TextColumn::make('venue')
                    ->searchable(),
                TextColumn::make('stadium')
                    ->searchable(),
                TextColumn::make('kickoff_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('home_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('away_score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('broadcast')
                    ->searchable(),
                TextColumn::make('ticket_external_url')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
