<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use App\Models\ProductInTransit;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;

class ViewReceipt extends ViewRecord
{
    protected static string $resource = ReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('receive')
                ->label('Принять товар')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(function (ProductInTransit $record): bool {
                    return $record->canBeReceived();
                })
                ->action(function (ProductInTransit $record): void {
                    if ($record->receive()) {
                        $this->notify('success', 'Товар успешно принят и добавлен в остатки на складе.');
                    } else {
                        $this->notify('danger', 'Ошибка при приемке товара. Попробуйте еще раз.');
                    }
                    $this->redirect(ReceiptResource::getUrl('index'));
                })
                ->requiresConfirmation()
                ->modalHeading('Принять товар')
                ->modalDescription('Товар будет перемещен в остатки на складе.')
                ->modalSubmitActionLabel('Принять'),
        ];
    }
}
