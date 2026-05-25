<?php

namespace App\Filament\Resources\ClubStaff\Pages;

use App\Filament\Resources\ClubStaff\ClubStaffResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClubStaff extends EditRecord
{
    protected static string $resource = ClubStaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
