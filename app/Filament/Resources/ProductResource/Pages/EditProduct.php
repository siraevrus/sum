<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Удалить'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Обрабатываем характеристики
        $attributes = [];
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'attribute_') && $value !== null) {
                $attributeName = str_replace('attribute_', '', $key);
                $attributes[$attributeName] = $value;
            }
        }
        $data['attributes'] = $attributes;

        // Удаляем временные поля характеристик
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'attribute_')) {
                unset($data[$key]);
            }
        }

        // Убеждаемся, что attributes всегда установлен
        if (! isset($data['attributes'])) {
            $data['attributes'] = [];
        }

        // Рассчитываем и сохраняем объем
        if (isset($data['product_template_id']) && isset($data['attributes']) && ! empty($data['attributes'])) {
            $template = \App\Models\ProductTemplate::find($data['product_template_id']);
            if ($template && $template->formula) {
                // Используем характеристики для формулы и добавляем количество
                $attributes = $data['attributes'];
                if (isset($data['quantity'])) {
                    $attributes['quantity'] = $data['quantity'];
                }

                // Формируем наименование из характеристик с правильным разделителем
                $formulaParts = [];
                $regularParts = [];

                foreach ($template->attributes as $templateAttribute) {
                    $attributeKey = $templateAttribute->variable;
                    if ($templateAttribute->type !== 'text' && isset($attributes[$attributeKey]) && $attributes[$attributeKey] !== null) {
                        if ($templateAttribute->is_in_formula) {
                            $formulaParts[] = $attributes[$attributeKey];
                        } else {
                            $regularParts[] = $attributes[$attributeKey];
                        }
                    }
                }

                if (! empty($formulaParts) || ! empty($regularParts)) {
                    $templateName = $template->name ?? 'Товар';
                    $generatedName = $templateName;

                    if (! empty($formulaParts)) {
                        $generatedName .= ': '.implode(' x ', $formulaParts);
                    }

                    if (! empty($regularParts)) {
                        if (! empty($formulaParts)) {
                            $generatedName .= ', '.implode(', ', $regularParts);
                        } else {
                            $generatedName .= ': '.implode(', ', $regularParts);
                        }
                    }

                    $data['name'] = $generatedName;
                }

                \Log::info('Quantity for formula', ['quantity' => $data['quantity'] ?? null]);
                \Log::info('Attributes for formula (EditProduct)', $attributes);
                $testResult = $template->testFormula($attributes);
                \Log::info('Formula result (EditProduct)', $testResult);
                if ($testResult['success']) {
                    $result = $testResult['result'];
                    $data['calculated_volume'] = $result;
                }
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Загружаем характеристики в отдельные поля для формы
        if (isset($data['attributes']) && is_array($data['attributes'])) {
            $template = \App\Models\ProductTemplate::find($data['product_template_id']);

            foreach ($data['attributes'] as $key => $value) {
                if ($template) {
                    // Находим атрибут шаблона
                    $templateAttribute = $template->attributes->where('variable', $key)->first();
                    if ($templateAttribute && $templateAttribute->type === 'select') {
                        // Для селектов находим индекс значения
                        $options = $templateAttribute->options_array;
                        $index = array_search($value, $options);
                        $data["attribute_{$key}"] = $index !== false ? $index : null;
                    } else {
                        $data["attribute_{$key}"] = $value;
                    }
                } else {
                    $data["attribute_{$key}"] = $value;
                }
            }
        }

        // Рассчитываем объем при загрузке данных
        if (isset($data['product_template_id']) && isset($data['attributes']) && is_array($data['attributes'])) {
            $template = \App\Models\ProductTemplate::find($data['product_template_id']);
            if ($template && $template->formula && ! empty($data['attributes'])) {
                // Создаем копию атрибутов для формулы, включая quantity
                $formulaAttributes = $data['attributes'];
                if (isset($data['quantity']) && is_numeric($data['quantity']) && $data['quantity'] > 0) {
                    $formulaAttributes['quantity'] = $data['quantity'];
                }

                \Log::info('BeforeFill (EditProduct): Attributes for formula', [
                    'template' => $template->name,
                    'attributes' => $data['attributes'],
                    'formula_attributes' => $formulaAttributes,
                    'quantity' => $data['quantity'] ?? 'not set',
                ]);

                $testResult = $template->testFormula($formulaAttributes);
                if ($testResult['success']) {
                    $result = $testResult['result'];

                    // Применяем валидацию как в ProductResource
                    $maxValue = 999999999.9999; // Максимум для decimal(15,4)
                    if ($result > $maxValue) {
                        \Log::warning('BeforeFill (EditProduct): Volume exceeds maximum value', [
                            'calculated_volume' => $result,
                            'max_value' => $maxValue,
                        ]);
                        $data['calculated_volume'] = null;
                    } else {
                        $data['calculated_volume'] = $result;
                    }

                    \Log::info('BeforeFill (EditProduct): Volume calculated', ['result' => $result]);
                } else {
                    \Log::warning('BeforeFill (EditProduct): Volume calculation failed', [
                        'error' => $testResult['error'],
                        'attributes' => $formulaAttributes,
                    ]);
                }
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
