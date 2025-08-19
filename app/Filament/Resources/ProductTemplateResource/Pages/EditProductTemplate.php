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
                ProductAttribute::create([
                    'product_template_id' => $this->record->id,
                    'name' => $attribute['name'],
                    'variable' => $attribute['variable'],
                    'type' => $attribute['type'] ?? 'number',
                    'options' => $attribute['options'] ?? null,
                    'unit' => $attribute['unit'] ?? null,
                    'is_required' => $attribute['is_required'] ?? false,
                    'is_in_formula' => $attribute['is_in_formula'] ?? false,
                    'sort_order' => $index + 1,
                ]);
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
