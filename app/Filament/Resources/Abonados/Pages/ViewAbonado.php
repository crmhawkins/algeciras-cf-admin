<?php

namespace App\Filament\Resources\Abonados\Pages;

use App\Filament\Resources\Abonados\AbonadoResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAbonado extends ViewRecord
{
    protected static string $resource = AbonadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Editar abonado'),
        ];
    }
}
