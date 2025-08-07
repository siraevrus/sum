<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Удалить'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Рассчитываем общую сумму
        $data['total_price'] = ($data['cash_amount'] ?? 0) + ($data['nocash_amount'] ?? 0);
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 