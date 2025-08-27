<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use App\Models\ProductInTransit;
use App\Models\ProductTemplate;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateReceipt extends CreateRecord
{
    protected static string $resource = ReceiptResource::class;

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
            'shipment_number' => $data['shipment_number'] ?? null,
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'shipping_location' => $data['shipping_location'] ?? null,
            'shipping_date' => $data['shipping_date'] ?? now()->toDateString(),
            'transport_number' => $data['transport_number'] ?? null,
            'expected_arrival_date' => $data['expected_arrival_date'] ?? null,
            'actual_arrival_date' => $data['actual_arrival_date'] ?? null,
            'status' => $data['status'] ?? ProductInTransit::STATUS_ARRIVED,
            'notes' => $data['notes'] ?? null,
            'created_by' => $createdBy,
            'is_active' => true,
            'attributes' => $attributes,
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
        $mainRecord = ProductInTransit::create($recordData);

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
                    'shipment_number' => $data['shipment_number'] ?? null,
                    'warehouse_id' => $data['warehouse_id'] ?? null,
                    'shipping_location' => $data['shipping_location'] ?? null,
                    'shipping_date' => $data['shipping_date'] ?? now()->toDateString(),
                    'transport_number' => $data['transport_number'] ?? null,
                    'expected_arrival_date' => $data['expected_arrival_date'] ?? null,
                    'actual_arrival_date' => $data['actual_arrival_date'] ?? null,
                    'status' => $data['status'] ?? ProductInTransit::STATUS_ARRIVED,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $createdBy,
                    'is_active' => true,
                    'attributes' => $productAttributes,
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

                ProductInTransit::create($additionalProductData);
            }
        }

        return $mainRecord;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
