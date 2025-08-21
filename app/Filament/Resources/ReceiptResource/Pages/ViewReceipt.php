<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use App\Models\ProductInTransit;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

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
                        Notification::make()
                            ->title('Товар успешно принят')
                            ->body('Товар добавлен в остатки на складе.')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Ошибка при приемке')
                            ->body('Не удалось принять товар. Попробуйте еще раз.')
                            ->danger()
                            ->send();
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
