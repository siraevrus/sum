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
}
