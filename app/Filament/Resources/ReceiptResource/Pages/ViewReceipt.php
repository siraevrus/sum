<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;

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
                ->visible(function (Product $record): bool {
                    return $record->status === Product::STATUS_FOR_RECEIPT;
                })
                ->action(function (Product $record): void {
                    $record->update([
                        'status' => Product::STATUS_IN_STOCK,
                        'actual_arrival_date' => now(),
                    ]);

                    Notification::make()
                        ->title('Товар успешно принят')
                        ->body('Товар добавлен в остатки на складе.')
                        ->success()
                        ->send();

                    $this->redirect(ReceiptResource::getUrl('index'));
                })
                ->requiresConfirmation()
                ->modalHeading('Принять товар')
                ->modalDescription('Товар будет перемещен в остатки на складе.')
                ->modalSubmitActionLabel('Принять'),
        ];
    }
}
