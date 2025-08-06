<?php

namespace App\Filament\Resources\ProductInTransitResource\Pages;

use App\Filament\Resources\ProductInTransitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductInTransit extends EditRecord
{
    protected static string $resource = ProductInTransitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label(''),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 