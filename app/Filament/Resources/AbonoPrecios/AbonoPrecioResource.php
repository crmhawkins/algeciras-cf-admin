<?php

namespace App\Filament\Resources\AbonoPrecios;

use App\Filament\Resources\AbonoPrecios\Pages\CreateAbonoPrecio;
use App\Filament\Resources\AbonoPrecios\Pages\EditAbonoPrecio;
use App\Filament\Resources\AbonoPrecios\Pages\ListAbonoPrecios;
use App\Filament\Resources\AbonoPrecios\Schemas\AbonoPrecioForm;
use App\Filament\Resources\AbonoPrecios\Tables\AbonoPreciosTable;
use App\Models\AbonoPrecio;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * "Configurar precios de los abonos" — réplica del módulo del proveedor
 * (compralaentrada /admin/abonos/precios). Lista de tipos de abono
 * (zona × alta nueva/renovación × adulto/infantil) con su precio.
 */
class AbonoPrecioResource extends Resource
{
    protected static ?string $model = AbonoPrecio::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyEuro;

    protected static string|UnitEnum|null $navigationGroup = 'Datos económicos';

    protected static ?int $navigationSort = 216;

    protected static ?string $navigationLabel = 'Precios de abonos';

    protected static ?string $modelLabel = 'tipo de abono';

    protected static ?string $pluralModelLabel = 'precios de abonos';

    protected static ?string $recordTitleAttribute = 'descripcion';

    public static function form(Schema $schema): Schema
    {
        return AbonoPrecioForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AbonoPreciosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListAbonoPrecios::route('/'),
            'create' => CreateAbonoPrecio::route('/create'),
            'edit'   => EditAbonoPrecio::route('/{record}/edit'),
        ];
    }
}
