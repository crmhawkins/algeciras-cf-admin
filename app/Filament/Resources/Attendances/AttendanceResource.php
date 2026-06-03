<?php

namespace App\Filament\Resources\Attendances;

use App\Filament\Resources\Attendances\Pages\ListAttendances;
use App\Filament\Resources\Attendances\Pages\ViewAttendance;
use App\Filament\Resources\Attendances\Schemas\AttendanceInfolist;
use App\Filament\Resources\Attendances\Tables\AttendancesTable;
use App\Models\Attendance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    protected static string|UnitEnum|null $navigationGroup = 'Atención al usuario';

    protected static ?int $navigationSort = 140;

    protected static ?string $navigationLabel = 'Accesos al estadio';

    protected static ?string $modelLabel = 'Acceso';

    protected static ?string $pluralModelLabel = 'Accesos';

    protected static ?string $recordTitleAttribute = 'id';

    public static function infolist(Schema $schema): Schema
    {
        return AttendanceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AttendancesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        // Los accesos se crean automáticamente al escanear el QR en puerta.
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAttendances::route('/'),
            'view'  => ViewAttendance::route('/{record}'),
        ];
    }
}
