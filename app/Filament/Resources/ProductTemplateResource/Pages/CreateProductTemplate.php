<?php

namespace App\Filament\Resources\ProductTemplateResource\Pages;

use App\Filament\Resources\ProductTemplateResource;
use App\Models\ProductAttribute;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;


class CreateProductTemplate extends CreateRecord
{
    protected static string $resource = ProductTemplateResource::class;

    protected function afterCreate(): void
    {
        $this->saveAttributes();
    }

    private function saveAttributes(): void
    {
        $attributes = $this->data['attributes'] ?? [];
        
        // Логируем для отладки
        // Log::info('Creating product template attributes', [
        //     'template_id' => $this->record->id,
        //     'attributes_data' => $attributes
        // ]);
        
        foreach ($attributes as $index => $attribute) {
            // Проверяем, что все обязательные поля заполнены
            if (!empty($attribute['name']) && !empty($attribute['variable'])) {
                try {
                    // Обрабатываем options для select типа
                    $options = null;
                    if (isset($attribute['type']) && $attribute['type'] === 'select' && !empty($attribute['options'])) {
                        if (is_string($attribute['options'])) {
                            // Разбиваем строку на массив по запятой
                            $options = array_map('trim', explode(',', $attribute['options']));
                        } elseif (is_array($attribute['options'])) {
                            $options = $attribute['options'];
                        }
                    }
                    
                    ProductAttribute::create([
                        'product_template_id' => $this->record->id,
                        'name' => trim($attribute['name']),
                        'variable' => trim($attribute['variable']),
                        'type' => $attribute['type'] ?? 'number',
                        'options' => $options,
                        'unit' => $attribute['unit'] ?? null,
                        'is_required' => $attribute['is_required'] ?? false,
                        'is_in_formula' => $attribute['is_in_formula'] ?? false,
                        'sort_order' => $index + 1,
                    ]);
                    
                    // Логируем успешное создание
                    // Log::info('Attribute created successfully', [
                    //     'attribute' => $attribute,
                    //     'created_id' => $this->record->id
                    // ]);
                    
                } catch (\Exception $e) {
                    // Логируем ошибку
                    // Log::error('Error creating attribute', [
                    //     'attribute' => $attribute,
                    //     'error' => $e->getMessage(),
                    //     'template_id' => $this->record->id
                    // ]);
                    
                    // Продолжаем создание других характеристик
                    continue;
                }
            }
        }
    }
}
