<?php

namespace App\Filament\Resources\ProductInTransitResource\Pages;

use App\Filament\Resources\ProductInTransitResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProductInTransit extends ViewRecord
{
    protected static string $resource = ProductInTransitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Изменить'),
        ];
    }
} 