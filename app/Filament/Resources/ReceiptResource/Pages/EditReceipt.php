<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReceipt extends EditRecord
{
    protected static string $resource = ReceiptResource::class;

    public static function canEdit($record): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (! $user) {
            return false;
        }

        // Редактирование доступно только админу и работнику склада
        return in_array($user->role->value, [
            'admin',
            'warehouse_worker',
        ]);
    }

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
                            ->body('Произошла ошибка: '.$e->getMessage())
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Обрабатываем характеристики из repeater
        $products = $data['products'] ?? [];
        if (! empty($products)) {
            $firstProduct = $products[0];

            // Собираем характеристики
            $attributes = [];
            foreach ($firstProduct as $key => $value) {
                if (str_starts_with($key, 'attribute_') && $value !== null && $value !== '') {
                    $attributeName = str_replace('attribute_', '', $key);
                    $attributes[$attributeName] = $value;
                }
            }

            // Удаляем временные поля характеристик
            foreach ($firstProduct as $key => $value) {
                if (str_starts_with($key, 'attribute_')) {
                    unset($firstProduct[$key]);
                }
            }

            // Обновляем основные поля
            $data['attributes'] = $attributes;
            $data['product_template_id'] = $firstProduct['product_template_id'] ?? null;
            $data['producer_id'] = $firstProduct['producer_id'] ?? null;
            $data['name'] = $firstProduct['name'] ?? null;
            $data['quantity'] = $firstProduct['quantity'] ?? 1;
            $data['calculated_volume'] = $firstProduct['calculated_volume'] ?? null;

            // Рассчитываем объем, если есть шаблон и характеристики
            if (! empty($data['product_template_id']) && ! empty($attributes)) {
                $template = \App\Models\ProductTemplate::find($data['product_template_id']);
                if ($template && $template->formula) {
                    // Создаем копию атрибутов для формулы, включая quantity
                    $formulaAttributes = $attributes;
                    if (isset($data['quantity']) && is_numeric($data['quantity']) && $data['quantity'] > 0) {
                        $formulaAttributes['quantity'] = $data['quantity'];
                    }

                    // Логируем атрибуты для отладки
                    \Log::info('EditReceipt: Attributes for formula', [
                        'template' => $template->name,
                        'attributes' => $attributes,
                        'formula_attributes' => $formulaAttributes,
                        'quantity' => $data['quantity'] ?? 'not set',
                        'formula' => $template->formula,
                    ]);

                    $testResult = $template->testFormula($formulaAttributes);
                    \Log::info('EditReceipt: Formula result', $testResult);

                    if ($testResult['success']) {
                        $result = $testResult['result'];
                        $data['calculated_volume'] = $result;
                        \Log::info('EditReceipt: Volume calculated and saved', [
                            'calculated_volume' => $result,
                        ]);
                    } else {
                        \Log::warning('EditReceipt: Volume calculation failed', [
                            'error' => $testResult['error'],
                            'attributes' => $formulaAttributes,
                        ]);
                    }
                }

                // Формируем наименование из характеристик
                if (! empty($attributes)) {
                    $nameParts = [];
                    foreach ($template->attributes as $templateAttribute) {
                        $attributeKey = $templateAttribute->variable;
                        if ($templateAttribute->type !== 'text' && isset($attributes[$attributeKey]) && $attributes[$attributeKey] !== null && $attributes[$attributeKey] !== '') {
                            $nameParts[] = $attributes[$attributeKey];
                        }
                    }

                    if (! empty($nameParts)) {
                        $templateName = $template->name ?? 'Товар';
                        $data['name'] = $templateName.': '.implode(', ', $nameParts);
                        \Log::info('EditReceipt: Name generated', ['name' => $data['name']]);
                    } else {
                        // Если не удалось сформировать имя из характеристик, используем название шаблона
                        $data['name'] = $template->name ?? 'Товар';
                        \Log::info('EditReceipt: Using template name', ['name' => $data['name']]);
                    }
                }
            }

            // Удаляем поле products, так как оно не нужно в основной модели
            unset($data['products']);
        }

        return $data;
    }
}
