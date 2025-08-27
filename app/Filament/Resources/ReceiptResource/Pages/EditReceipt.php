<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use App\Models\ProductInTransit;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReceipt extends EditRecord
{
    protected static string $resource = ReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('receive')
                ->label('Принять товар')
                ->requiresConfirmation()
                ->action(function () {
                    /** @var ProductInTransit $product */
                    $product = $this->record;
                    $product->updateStatus(ProductInTransit::STATUS_RECEIVED);
                })
                ->visible(fn (): bool => $this->record instanceof ProductInTransit && in_array($this->record->status, [ProductInTransit::STATUS_ARRIVED, ProductInTransit::STATUS_IN_TRANSIT])),
        ];
    }
}
