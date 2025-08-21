<?php

namespace App\Filament\Resources\ProductInTransitResource\Pages;

use App\Filament\Resources\ProductInTransitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductInTransit extends EditRecord
{
    protected static string $resource = ProductInTransitResource::class;

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
        
        // Обрабатываем document_path для сохранения
        if (isset($data['document_path'])) {
            // Если document_path пустой или null, устанавливаем пустой массив
            if (empty($data['document_path'])) {
                $data['document_path'] = [];
            }
            // Убеждаемся, что document_path всегда массив
            if (!is_array($data['document_path'])) {
                $data['document_path'] = [$data['document_path']];
            }
            // Фильтруем пустые значения
            $data['document_path'] = array_filter($data['document_path'], function($path) {
                return !empty($path);
            });
        }
        
        // Рассчитываем и сохраняем объем
        if (isset($data['product_template_id']) && isset($data['attributes']) && !empty($data['attributes'])) {
            $template = \App\Models\ProductTemplate::find($data['product_template_id']);
            if ($template && $template->formula) {
                // Добавляем количество в атрибуты для формулы
                $attributes = $data['attributes'];
                $attributes['quantity'] = $data['quantity'] ?? 1;
                
                $testResult = $template->testFormula($attributes);
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
        
        // Обрабатываем document_path для корректной работы FileUpload
        if (isset($data['document_path']) && is_array($data['document_path'])) {
            // Убеждаемся, что document_path содержит корректные пути к файлам
            $data['document_path'] = array_filter($data['document_path'], function($path) {
                return !empty($path) && is_string($path);
            });
        }
        
        // Рассчитываем объем при загрузке данных
        if (isset($data['product_template_id']) && isset($data['attributes']) && is_array($data['attributes'])) {
            $template = \App\Models\ProductTemplate::find($data['product_template_id']);
            if ($template && $template->formula && !empty($data['attributes'])) {
                // Добавляем количество в атрибуты для формулы
                $attributes = $data['attributes'];
                $attributes['quantity'] = $data['quantity'] ?? 1;
                
                $testResult = $template->testFormula($attributes);
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