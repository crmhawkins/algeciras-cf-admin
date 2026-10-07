<?php

namespace App\Filament\Resources\Abonados;

use App\Filament\Resources\Abonados\Pages\EditAbonado;
use App\Filament\Resources\Abonados\Pages\ListAbonados;
use App\Filament\Resources\Abonados\Pages\ViewAbonado;
use App\Filament\Resources\Abonados\Schemas\AbonadoInfolist;
use App\Filament\Resources\Abonados\Tables\AbonadosTable;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Models\Customer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * ABONADOS — réplica del módulo "Abonados" del proveedor (compralaentrada).
 *
 * Un "abonado" es un Customer con número de socio (los 3337 del padrón + nuevas
 * altas). Reutiliza el form/infolist de Customer mientras montamos la ficha
 * dedicada (tarea #121). El listado y las acciones replican el del proveedor.
 */
class AbonadoResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = null;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Abonados';

    protected static ?string $modelLabel = 'abonado';

    protected static ?string $pluralModelLabel = 'abonados';

    protected static ?string $recordTitleAttribute = 'full_name';

    /** Solo customers con número de abonado (los abonados reales). */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('socio_number');
    }

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AbonadoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AbonadosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAbonados::route('/'),
            'view'  => ViewAbonado::route('/{record}'),
            'edit'  => EditAbonado::route('/{record}/edit'),
        ];
    }
}
