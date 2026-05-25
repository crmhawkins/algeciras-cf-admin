<?php

namespace App\Filament\Resources\ClubStaff;

use App\Filament\Resources\ClubStaff\Pages\CreateClubStaff;
use App\Filament\Resources\ClubStaff\Pages\EditClubStaff;
use App\Filament\Resources\ClubStaff\Pages\ListClubStaff;
use App\Filament\Resources\ClubStaff\Schemas\ClubStaffForm;
use App\Filament\Resources\ClubStaff\Tables\ClubStaffTable;
use App\Models\ClubStaff;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ClubStaffResource extends Resource
{
    protected static ?string $model = ClubStaff::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return ClubStaffForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ClubStaffTable::configure($table);
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
            'index' => ListClubStaff::route('/'),
            'create' => CreateClubStaff::route('/create'),
            'edit' => EditClubStaff::route('/{record}/edit'),
        ];
    }
}
