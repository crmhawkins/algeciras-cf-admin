<?php

namespace App\Filament\Resources\AbonoPrecios\Pages;

use App\Filament\Resources\AbonoPrecios\AbonoPrecioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAbonoPrecios extends ListRecords
{
    protected static string $resource = AbonoPrecioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Crear nuevo tipo de abono'),
        ];
    }
}
