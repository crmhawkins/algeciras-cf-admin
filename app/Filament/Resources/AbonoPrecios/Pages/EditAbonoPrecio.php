<?php

namespace App\Filament\Resources\AbonoPrecios\Pages;

use App\Filament\Resources\AbonoPrecios\AbonoPrecioResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAbonoPrecio extends EditRecord
{
    protected static string $resource = AbonoPrecioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
