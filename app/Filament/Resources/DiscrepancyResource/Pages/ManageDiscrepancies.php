<?php

namespace App\Filament\Resources\DiscrepancyResource\Pages;

use App\Filament\Resources\DiscrepancyResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageDiscrepancies extends ManageRecords
{
    protected static string $resource = DiscrepancyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
