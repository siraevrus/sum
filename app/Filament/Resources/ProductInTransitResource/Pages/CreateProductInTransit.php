<?php

namespace App\Filament\Resources\ProductInTransitResource\Pages;

use App\Filament\Resources\ProductInTransitResource;
use App\Models\Product;
use App\Models\ProductInTransit;
use App\Models\ProductTemplate;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateProductInTransit extends CreateRecord
{
    protected static string $resource = ProductInTransitResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $createdBy = Auth::id();
        $products = $data['products'] ?? [];

        // Создаем первый товар как основную запись
        $firstProduct = $products[0] ?? [];
        if (empty($firstProduct)) {
            throw new \Exception('Необходимо добавить хотя бы один товар');
        }

        // Собираем характеристики для первого товара
        $attributes = [];
        foreach ($firstProduct as $key => $value) {
            if (str_starts_with($key, 'attribute_') && $value !== null) {
                $attributeName = str_replace('attribute_', '', $key);
                $attributes[$attributeName] = $value;
            }
        }

        // Удаляем временные поля характеристик
        foreach ($firstProduct as $key => $value) {
            if (str_starts_with($key, 'attribute_')) {
                unset($firstProduct[$key]);
            }
        }

        $recordData = array_merge($firstProduct, [
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'shipping_location' => $data['shipping_location'] ?? null,
            'shipping_date' => $data['shipping_date'] ?? now()->toDateString(),
            'transport_number' => $data['transport_number'] ?? null,
            'tracking_number' => $data['tracking_number'] ?? null,
            'expected_arrival_date' => $data['expected_arrival_date'] ?? null,
            'status' => Product::STATUS_IN_TRANSIT,
            'notes' => $data['notes'] ?? null,
            'document_path' => $data['document_path'] ?? null,
            'created_by' => $createdBy,
            'is_active' => true,
            'attributes' => $attributes,
            // arrival_date обязательна в products — ставим как дата отгрузки или сегодня
            'arrival_date' => ($data['shipping_date'] ?? null) ?: now()->toDateString(),
        ]);

        // Рассчет объема для первого товара
        if (! empty($recordData['product_template_id'])) {
            $template = ProductTemplate::find($recordData['product_template_id']);
            if ($template && $template->formula) {
                $attrsForFormula = [];

                // Собираем числовые характеристики для формулы
                foreach ($attributes as $key => $value) {
                    if (is_numeric($value)) {
                        $attrsForFormula[$key] = (float) $value;
                    }
                }

                // Добавляем количество
                $attrsForFormula['quantity'] = (int) ($recordData['quantity'] ?? 1);

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
                    $recordData['name'] = $templateName.': '.implode(', ', $nameParts);
                }

                if (! empty($attrsForFormula)) {
                    $testResult = $template->testFormula($attrsForFormula);
                    if ($testResult['success']) {
                        $recordData['calculated_volume'] = (float) $testResult['result'];
                    }
                }
            }
        }

        // Создаем основную запись
        $mainRecord = Product::create($recordData);

        // Создаем дополнительные товары, если их больше одного
        if (count($products) > 1) {
            for ($i = 1; $i < count($products); $i++) {
                $product = $products[$i];

                // Собираем характеристики
                $productAttributes = [];
                foreach ($product as $key => $value) {
                    if (str_starts_with($key, 'attribute_') && $value !== null) {
                        $attributeName = str_replace('attribute_', '', $key);
                        $productAttributes[$attributeName] = $value;
                    }
                }

                // Удаляем временные поля характеристик
                foreach ($product as $key => $value) {
                    if (str_starts_with($key, 'attribute_')) {
                        unset($product[$key]);
                    }
                }

                $additionalProductData = array_merge($product, [
                    'warehouse_id' => $data['warehouse_id'] ?? null,
                    'shipping_location' => $data['shipping_location'] ?? null,
                    'shipping_date' => $data['shipping_date'] ?? now()->toDateString(),
                    'transport_number' => $data['transport_number'] ?? null,
                    'tracking_number' => $data['tracking_number'] ?? null,
                    'expected_arrival_date' => $data['expected_arrival_date'] ?? null,
                    'status' => Product::STATUS_IN_TRANSIT,
                    'notes' => $data['notes'] ?? null,
                    'document_path' => $data['document_path'] ?? null,
                    'created_by' => $createdBy,
                    'is_active' => true,
                    'attributes' => $productAttributes,
                    'arrival_date' => ($data['shipping_date'] ?? null) ?: now()->toDateString(),
                ]);

                // Рассчет объема для дополнительного товара
                if (! empty($additionalProductData['product_template_id'])) {
                    $template = ProductTemplate::find($additionalProductData['product_template_id']);
                    if ($template && $template->formula) {
                        $attrsForFormula = [];

                        // Собираем числовые характеристики для формулы
                        foreach ($productAttributes as $key => $value) {
                            if (is_numeric($value)) {
                                $attrsForFormula[$key] = (float) $value;
                            }
                        }

                        // Добавляем количество
                        $attrsForFormula['quantity'] = (int) ($additionalProductData['quantity'] ?? 1);

                        // Формируем наименование из характеристик
                        $nameParts = [];
                        foreach ($template->attributes as $templateAttribute) {
                            $attributeKey = $templateAttribute->variable;
                            if (isset($productAttributes[$attributeKey]) && $productAttributes[$attributeKey] !== null) {
                                $nameParts[] = $productAttributes[$attributeKey];
                            }
                        }

                        if (! empty($nameParts)) {
                            // Добавляем название шаблона в начало
                            $templateName = $template->name ?? 'Товар';
                            $additionalProductData['name'] = $templateName.': '.implode(', ', $nameParts);
                        }

                        if (! empty($attrsForFormula)) {
                            $testResult = $template->testFormula($attrsForFormula);
                            if ($testResult['success']) {
                                $additionalProductData['calculated_volume'] = (float) $testResult['result'];
                            }
                        }
                    }
                }

                Product::create($additionalProductData);
            }
        }

        return $mainRecord;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function generateProductName(array $data): string
    {
        // Если наименование уже сгенерировано, используем его
        if (! empty($data['name'])) {
            return $data['name'];
        }

        // Иначе генерируем из характеристик
        $templateId = $data['product_template_id'] ?? null;
        if (! $templateId) {
            return $data['producer'] ?? 'Товар';
        }

        $template = ProductTemplate::find($templateId);
        if (! $template) {
            return $data['producer'] ?? 'Товар';
        }

        // Собираем характеристики
        $attributes = [];
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'attribute_') && $value !== null) {
                $attributeName = str_replace('attribute_', '', $key);
                $attributes[$attributeName] = $value;
            }
        }

        if (empty($attributes)) {
            return $template->name ?? 'Товар';
        }

        // Формируем наименование из характеристик
        $nameParts = [];
        foreach ($template->attributes as $templateAttribute) {
            $attributeKey = $templateAttribute->variable;
            if (isset($attributes[$attributeKey]) && $attributes[$attributeKey] !== null) {
                $nameParts[] = $attributes[$attributeKey];
            }
        }

        if (empty($nameParts)) {
            return $template->name ?? 'Товар';
        }

        // Добавляем название шаблона в начало
        $templateName = $template->name ?? 'Товар';

        return $templateName.': '.implode(', ', $nameParts);
    }
}
