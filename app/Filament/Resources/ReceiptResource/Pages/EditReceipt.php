<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use App\Models\Product;
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
                    /** @var Product $product */
                    $product = $this->record;
                    $product->markInStock();
                })
                ->visible(fn (): bool => $this->record instanceof Product && $this->record->isInTransit()),
        ];
    }
}
