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
        // Если данные пришли из репитера products — маппим первую позицию в поля записи
        if (isset($data['products']) && is_array($data['products']) && ! empty($data['products'])) {
            $first = $data['products'][0];

            // Собираем характеристики из репитера
            $attributes = [];
            foreach ($first as $key => $value) {
                if (str_starts_with($key, 'attribute_') && $value !== null) {
                    $attributeName = str_replace('attribute_', '', $key);
                    $attributes[$attributeName] = $value;
                }
            }
            $data['attributes'] = $attributes;

            // Прокидываем основные поля из репитера на верхний уровень
            foreach ([
                'product_template_id',
                'name',
                'producer',
                'quantity',
                'calculated_volume',
                'description',
            ] as $field) {
                if (array_key_exists($field, $first)) {
                    $data[$field] = $first[$field];
                }
            }

            // Подставляем имя производителя по producer_id
            if (isset($first['producer_id'])) {
                $producer = \App\Models\Producer::find($first['producer_id']);
                $data['producer_id'] = $first['producer_id'];
                $data['producer'] = $producer?->name;
            }

            unset($data['products']);
        } else {
            // Обрабатываем характеристики из плоских полей (fallback)
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
        }

        // Убеждаемся, что attributes всегда установлен
        if (! isset($data['attributes'])) {
            $data['attributes'] = [];
        }

        // Обрабатываем document_path для сохранения
        if (isset($data['document_path'])) {
            // Если document_path пустой или null, устанавливаем пустой массив
            if (empty($data['document_path'])) {
                $data['document_path'] = [];
            }
            // Убеждаемся, что document_path всегда массив
            if (! is_array($data['document_path'])) {
                $data['document_path'] = [$data['document_path']];
            }
            // Фильтруем пустые значения
            $data['document_path'] = array_filter($data['document_path'], function ($path) {
                return ! empty($path);
            });
        }

        // Рассчитываем и сохраняем объем и наименование (перенесено на сохранение, чтобы форма работала быстрее)
        if (isset($data['product_template_id']) && isset($data['attributes']) && ! empty($data['attributes'])) {
            $template = \App\Models\ProductTemplate::find($data['product_template_id']);
            if ($template && $template->formula) {
                // Добавляем количество в атрибуты для формулы
                $attributes = $data['attributes'];
                $attributes['quantity'] = $data['quantity'] ?? 1;

                // Формируем наименование из характеристик
                $nameParts = [];
                foreach ($template->attributes as $templateAttribute) {
                    $attributeKey = $templateAttribute->variable;
                    if (isset($attributes[$attributeKey]) && $attributes[$attributeKey] !== null) {
                        $nameParts[] = $attributes[$attributeKey];
                    }
                }

                if (! empty($nameParts)) {
                    // Добавляем название шаблона в начало
                    $templateName = $template->name ?? 'Товар';
                    $data['name'] = $templateName.': '.implode(', ', $nameParts);
                }

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
        // Префилл репитера products одной позицией на основе текущей записи
        $item = [
            'product_template_id' => $data['product_template_id'] ?? null,
            'name' => $data['name'] ?? null,
            'producer' => $data['producer'] ?? null,
            'producer_id' => $data['producer_id'] ?? null,
            'quantity' => $data['quantity'] ?? 1,
            'calculated_volume' => $data['calculated_volume'] ?? null,
            'description' => $data['description'] ?? null,
        ];

        if (isset($data['attributes']) && is_array($data['attributes'])) {
            foreach ($data['attributes'] as $key => $value) {
                $item["attribute_{$key}"] = $value;
            }
        }

        $data['products'] = [$item];

        // Обрабатываем document_path для корректной работы FileUpload
        if (isset($data['document_path']) && is_array($data['document_path'])) {
            // Убеждаемся, что document_path содержит корректные пути к файлам
            $data['document_path'] = array_filter($data['document_path'], function ($path) {
                return ! empty($path) && is_string($path);
            });
        }

        // Пересчет объема (оставляем как есть — будет выполняться реактивно в форме)

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Удалить'),
            Actions\Action::make('send_for_receipt')
                ->label('Отправить на приемку')
                ->color('success')
                ->icon('heroicon-o-arrow-right-circle')
                ->visible(fn () => $this->record->status !== 'for_receipt')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->status = 'for_receipt';
                    $this->record->save();
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->modalHeading('Отправить на приемку')
                ->modalDescription('Статус будет изменен на "Для приемки", и карточка закроется.'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
