<?php

namespace App\Filament\Resources\Eventos;

use App\Filament\Resources\Eventos\Pages\CreateEvento;
use App\Filament\Resources\Eventos\Pages\EditEvento;
use App\Filament\Resources\Eventos\Pages\ListEventos;
use App\Filament\Resources\Eventos\Schemas\EventoForm;
use App\Filament\Resources\Eventos\Tables\EventosTable;
use App\Models\FootballMatch;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * EVENTOS — réplica del módulo "Eventos" del proveedor (partidos con venta de
 * entradas). Cada evento es un FootballMatch.
 */
class EventoResource extends Resource
{
    protected static ?string $model = FootballMatch::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Eventos';

    protected static ?string $modelLabel = 'evento';

    protected static ?string $pluralModelLabel = 'eventos';

    protected static ?string $recordTitleAttribute = 'opponent';

    public static function form(Schema $schema): Schema
    {
        return EventoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListEventos::route('/'),
            'create' => CreateEvento::route('/create'),
            'edit'   => EditEvento::route('/{record}/edit'),
        ];
    }
}
