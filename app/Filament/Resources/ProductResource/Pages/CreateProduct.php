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
                // Используем только характеристики для формулы (без количества)
                $attributes = $data['attributes'];
                
                // Формируем наименование из характеристик
                $nameParts = [];
                foreach ($template->attributes as $templateAttribute) {
                    $attributeKey = $templateAttribute->variable;
                    if (isset($attributes[$attributeKey]) && $attributes[$attributeKey] !== null) {
                        $nameParts[] = $attributes[$attributeKey];
                    }
                }
                
                if (!empty($nameParts)) {
                    // Добавляем название шаблона в начало
                    $templateName = $template->name ?? 'Товар';
                    $data['name'] = $templateName . ': ' . implode(', ', $nameParts);
                }
                
                $testResult = $template->testFormula($attributes);
                if ($testResult['success']) {
                    $result = $testResult['result'];
                    
                    // Ограничиваем максимальное значение объема до 99999 (5 символов)
                    if ($result > 99999) {
                        $result = 99999;
                    }
                    
                    $data['calculated_volume'] = $result;
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
                if (isset($data['attributes']) && is_array($data['attributes']) && !empty($data['attributes'])) {
                    $testResult = $template->testFormula($data['attributes']);
                    if ($testResult['success']) {
                        $result = $testResult['result'];
                        
                        // Ограничиваем максимальное значение объема до 99999 (5 символов)
                        if ($result > 99999) {
                            $result = 99999;
                        }
                        
                        $data['calculated_volume'] = $result;
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