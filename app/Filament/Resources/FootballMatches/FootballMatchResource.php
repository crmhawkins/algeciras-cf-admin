<?php

namespace App\Filament\Resources\FootballMatches;

use App\Filament\Resources\FootballMatches\Pages\CreateFootballMatch;
use App\Filament\Resources\FootballMatches\Pages\EditFootballMatch;
use App\Filament\Resources\FootballMatches\Pages\ListFootballMatches;
use App\Filament\Resources\FootballMatches\Schemas\FootballMatchForm;
use App\Filament\Resources\FootballMatches\Tables\FootballMatchesTable;
use App\Models\FootballMatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FootballMatchResource extends Resource
{
    protected static ?string $model = FootballMatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return FootballMatchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FootballMatchesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFootballMatches::route('/'),
            'create' => CreateFootballMatch::route('/create'),
            'edit' => EditFootballMatch::route('/{record}/edit'),
        ];
    }
}
