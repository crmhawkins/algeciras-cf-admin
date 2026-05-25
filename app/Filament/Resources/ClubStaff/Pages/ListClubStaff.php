<?php

namespace App\Filament\Resources\ClubStaff\Pages;

use App\Filament\Resources\ClubStaff\ClubStaffResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClubStaff extends ListRecords
{
    protected static string $resource = ClubStaffResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
