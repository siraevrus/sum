<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();

        // Ensure warehouse_id is set for non-admin users
        $user = Auth::user();
        if (! isset($data['warehouse_id']) && $user && ! $user->isAdmin()) {
            $data['warehouse_id'] = $user->warehouse_id;
        }

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

        // Добавляем quantity в характеристики для формулы, если есть
        if (isset($data['quantity'])) {
            $attributes['quantity'] = $data['quantity'];
        }

        // Рассчитываем и сохраняем объем
        if (isset($data['product_template_id']) && isset($data['attributes']) && ! empty($data['attributes'])) {
            $template = \App\Models\ProductTemplate::find($data['product_template_id']);
            if ($template && $template->formula) {
                // Создаем копию атрибутов для формулы, включая quantity
                $formulaAttributes = $data['attributes'];
                if (isset($data['quantity']) && is_numeric($data['quantity']) && $data['quantity'] > 0) {
                    $formulaAttributes['quantity'] = $data['quantity'];
                }

                // Формируем наименование из характеристик
                $nameParts = [];
                foreach ($template->attributes as $templateAttribute) {
                    $attributeKey = $templateAttribute->variable;
                    if ($templateAttribute->type !== 'text' && isset($data['attributes'][$attributeKey]) && $data['attributes'][$attributeKey] !== null) {
                        $nameParts[] = $data['attributes'][$attributeKey];
                    }
                }

                if (! empty($nameParts)) {
                    // Добавляем название шаблона в начало
                    $templateName = $template->name ?? 'Товар';
                    $data['name'] = $templateName.': '.implode(', ', $nameParts);
                } else {
                    // Если нет характеристик, используем только название шаблона
                    $data['name'] = $template->name ?? 'Товар';
                }

                \Log::info('Attributes for formula (including quantity)', [
                    'template' => $template->name,
                    'attributes' => $data['attributes'],
                    'formula_attributes' => $formulaAttributes,
                    'quantity' => $data['quantity'] ?? 'not set',
                    'formula' => $template->formula,
                ]);

                $testResult = $template->testFormula($formulaAttributes);
                \Log::info('Formula result', $testResult);

                if ($testResult['success']) {
                    $result = $testResult['result'];
                    $data['calculated_volume'] = $result;
                    \Log::info('Volume calculated and saved', [
                        'calculated_volume' => $result,
                        'final_data' => $data,
                    ]);
                } else {
                    \Log::warning('Volume calculation failed', [
                        'error' => $testResult['error'],
                        'attributes' => $formulaAttributes,
                    ]);
                }
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Рассчитываем объем при загрузке формы, если есть шаблон
        if (isset($data['product_template_id'])) {
            $template = \App\Models\ProductTemplate::find($data['product_template_id']);
            if ($template && $template->formula) {
                // Если есть характеристики, рассчитываем объем
                if (isset($data['attributes']) && is_array($data['attributes']) && ! empty($data['attributes'])) {
                    // Создаем копию атрибутов для формулы, включая quantity
                    $formulaAttributes = $data['attributes'];
                    if (isset($data['quantity']) && is_numeric($data['quantity']) && $data['quantity'] > 0) {
                        $formulaAttributes['quantity'] = $data['quantity'];
                    }

                    \Log::info('BeforeFill: Attributes for formula', [
                        'template' => $template->name,
                        'attributes' => $data['attributes'],
                        'formula_attributes' => $formulaAttributes,
                        'quantity' => $data['quantity'] ?? 'not set',
                    ]);

                    $testResult = $template->testFormula($formulaAttributes);
                    if ($testResult['success']) {
                        $result = $testResult['result'];
                        $data['calculated_volume'] = $result;
                        \Log::info('BeforeFill: Volume calculated', ['result' => $result]);
                    } else {
                        \Log::warning('BeforeFill: Volume calculation failed', [
                            'error' => $testResult['error'],
                            'attributes' => $formulaAttributes,
                        ]);
                    }
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
