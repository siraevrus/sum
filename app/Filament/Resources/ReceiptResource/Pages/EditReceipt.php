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
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(function (Product $record): bool {
                    return $record->status === Product::STATUS_FOR_RECEIPT;
                })
                ->action(function (Product $record): void {
                    try {
                        // Обновляем статус и дату прибытия без сохранения формы
                        $record->update([
                            'status' => Product::STATUS_IN_STOCK,
                            'actual_arrival_date' => now(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Товар успешно принят')
                            ->body('Товар добавлен в остатки на складе.')
                            ->success()
                            ->send();

                        // Перенаправляем на список приемок
                        $this->redirect(ReceiptResource::getUrl('index'));
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Ошибка при принятии товара')
                            ->body('Произошла ошибка: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Принять товар')
                ->modalDescription('Товар будет перемещен в остатки на складе. Несохраненные изменения в форме будут потеряны.')
                ->modalSubmitActionLabel('Принять')
                ->extraAttributes([
                    'class' => 'action-receive-product',
                ]),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Преобразуем attributes в поля для репитера товаров (один товар)
        $item = [
            'product_template_id' => $data['product_template_id'] ?? null,
            'producer_id' => $data['producer_id'] ?? null,
            'name' => $data['name'] ?? null,
            'quantity' => $data['quantity'] ?? 1,
            'calculated_volume' => $data['calculated_volume'] ?? null,
            'description' => $data['description'] ?? null,
            'actual_arrival_date' => $data['actual_arrival_date'] ?? null,
        ];
        if (isset($data['attributes']) && is_array($data['attributes'])) {
            foreach ($data['attributes'] as $key => $value) {
                $item["attribute_{$key}"] = $value;
            }
        }
        $data['products'] = [$item];
        return $data;
    }
}
