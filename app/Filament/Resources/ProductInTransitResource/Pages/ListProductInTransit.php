<?php

namespace App\Filament\Resources\ProductInTransitResource\Pages;

use App\Filament\Resources\ProductInTransitResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductInTransit extends ListRecords
{
    protected static string $resource = ProductInTransitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Добавить'),
        ];
    }
} 