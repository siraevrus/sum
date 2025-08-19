<?php

namespace App\Filament\Resources\ProductTemplateResource\Pages;

use App\Filament\Resources\ProductTemplateResource;
use App\Models\ProductAttribute;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;


class EditProductTemplate extends EditRecord
{
    protected static string $resource = ProductTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Удалить'),
        ];
    }

    protected function afterSave(): void
    {
        $this->updateAttributes();
    }

    private function updateAttributes(): void
    {
        $attributes = $this->data['attributes'] ?? [];
        
        // Удаляем старые характеристики
        $this->record->attributes()->delete();
        
        // Создаем новые характеристики
        foreach ($attributes as $index => $attribute) {
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
                    
                } catch (\Exception $e) {
                    // Логируем ошибку
                    // Log::error('Error updating attribute', [
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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Загружаем характеристики для отображения в форме
        $data['attributes'] = $this->record->attributes->map(function ($attribute) {
            return [
                'name' => $attribute->name,
                'variable' => $attribute->variable,
                'type' => $attribute->type,
                'options' => $attribute->options,
                'unit' => $attribute->unit,
                'is_required' => $attribute->is_required,
                'is_in_formula' => $attribute->is_in_formula,
            ];
        })->toArray();
        
        return $data;
    }
}
