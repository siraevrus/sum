<?php

namespace App\Filament\Resources\RequestResource\Pages;

use App\Filament\Resources\RequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRequest extends EditRecord
{
    protected static string $resource = RequestResource::class;

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
        if (!isset($data['attributes'])) {
            $data['attributes'] = [];
        }
        
        // Рассчитываем и сохраняем объем
        if (isset($data['product_template_id']) && isset($data['attributes']) && !empty($data['attributes'])) {
            $template = \App\Models\ProductTemplate::find($data['product_template_id']);
            if ($template && $template->formula) {
                $testResult = $template->testFormula($data['attributes']);
                if ($testResult['success']) {
                    $data['calculated_volume'] = $testResult['result'];
                }
            }
        }
        
        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Загружаем характеристики в отдельные поля для формы
        if (isset($data['attributes']) && is_array($data['attributes'])) {
            foreach ($data['attributes'] as $key => $value) {
                $data["attribute_{$key}"] = $value;
            }
        }
        
        // Рассчитываем объем при загрузке данных
        if (isset($data['product_template_id']) && isset($data['attributes']) && is_array($data['attributes'])) {
            $template = \App\Models\ProductTemplate::find($data['product_template_id']);
            if ($template && $template->formula && !empty($data['attributes'])) {
                $testResult = $template->testFormula($data['attributes']);
                if ($testResult['success']) {
                    $data['calculated_volume'] = $testResult['result'];
                }
            }
        }
        
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Удалить'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 