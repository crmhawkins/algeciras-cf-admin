<?php

namespace App\Filament\Resources\Abonados\Pages;

use App\Filament\Resources\Abonados\AbonadoResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAbonado extends EditRecord
{
    protected static string $resource = AbonadoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->label('Ver abonado'),
            DeleteAction::make()->label('Borrar abonado'),
        ];
    }
}
