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
        // Логируем исходные данные для отладки
        \Log::info('EditReceipt: mutateFormDataBeforeFill input', [
            'has_attributes' => isset($data['attributes']),
            'attributes_type' => gettype($data['attributes'] ?? 'not set'),
            'attributes' => $data['attributes'] ?? 'not set',
            'product_template_id' => $data['product_template_id'] ?? 'not set',
        ]);

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
                \Log::info("EditReceipt: Adding attribute", [
                    'key' => $key,
                    'value' => $value,
                    'field_name' => "attribute_{$key}"
                ]);
            }
        } elseif (isset($data['attributes']) && is_string($data['attributes'])) {
            // Если attributes сохранены как JSON строка
            $attributes = json_decode($data['attributes'], true);
            if (is_array($attributes)) {
                foreach ($attributes as $key => $value) {
                    $item["attribute_{$key}"] = $value;
                    \Log::info("EditReceipt: Adding attribute from JSON", [
                        'key' => $key,
                        'value' => $value,
                        'field_name' => "attribute_{$key}"
                    ]);
                }
            }
        }
        
        $data['products'] = [$item];

        \Log::info('EditReceipt: mutateFormDataBeforeFill output', [
            'item_keys' => array_keys($item),
            'products_count' => count($data['products']),
            'first_product_keys' => array_keys($data['products'][0]),
        ]);

        return $data;
    }


    protected function mutateFormDataBeforeSave(array $data): array
    {
        // В режиме редактирования приемки используем существующие характеристики из записи
        $record = $this->getRecord();
        $existingAttributes = [];
        if ($record && $record->attributes) {
            $existingAttributes = is_array($record->attributes) ? $record->attributes : json_decode($record->attributes, true) ?? [];
        }

        // Обрабатываем характеристики из repeater
        $products = $data['products'] ?? [];
        if (! empty($products)) {
            $firstProduct = $products[0];

            // Собираем характеристики (хотя они должны быть отключены)
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

            // Обновляем основные поля - сохраняем существующие характеристики
            $data['attributes'] = $existingAttributes;
            $data['product_template_id'] = $firstProduct['product_template_id'] ?? null;
            $data['producer_id'] = $firstProduct['producer_id'] ?? null;
            $data['name'] = $firstProduct['name'] ?? null;
            $data['quantity'] = $firstProduct['quantity'] ?? 1;
            $data['calculated_volume'] = $firstProduct['calculated_volume'] ?? null;

            // Генерируем имя, если оно пустое
            if (empty($data['name']) && !empty($data['product_template_id'])) {
                $template = \App\Models\ProductTemplate::find($data['product_template_id']);
                if ($template && !empty($existingAttributes)) {
                    $nameParts = [];
                    foreach ($template->attributes as $attribute) {
                        if (isset($existingAttributes[$attribute->variable]) && !empty($existingAttributes[$attribute->variable])) {
                            $nameParts[] = $existingAttributes[$attribute->variable];
                        }
                    }
                    
                    if (!empty($nameParts)) {
                        $data['name'] = $template->name . ': ' . implode(', ', $nameParts);
                    } else {
                        $data['name'] = $template->name ?? 'Товар';
                    }
                }
            }

            // Если имя все еще пустое, используем запасной вариант
            if (empty($data['name'])) {
                $data['name'] = $record->name ?? 'Товар';
            }

            // Используем существующие характеристики для расчета объема
            if (! empty($data['product_template_id']) && ! empty($existingAttributes)) {
                $template = \App\Models\ProductTemplate::find($data['product_template_id']);
                if ($template && $template->formula) {
                    // Создаем копию существующих атрибутов для формулы, включая quantity
                    $formulaAttributes = $existingAttributes;
                    if (isset($data['quantity']) && is_numeric($data['quantity']) && $data['quantity'] > 0) {
                        $formulaAttributes['quantity'] = $data['quantity'];
                    }

                    // Логируем атрибуты для отладки
                    \Log::info('EditReceipt: Attributes for formula', [
                        'template' => $template->name,
                        'existing_attributes' => $existingAttributes,
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
            } else {
                // Если нет характеристик для расчета, сохраняем существующий объем
                \Log::info('EditReceipt: No attributes for volume calculation, keeping existing volume', [
                    'existing_volume' => $data['calculated_volume'] ?? 'not set',
                    'attributes_count' => count($attributes ?? []),
                ]);
            }

            // Формируем наименование из характеристик
            if (! empty($attributes)) {
                $template = \App\Models\ProductTemplate::find($data['product_template_id']);
                if ($template) {
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
