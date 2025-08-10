<?php

namespace App\Filament\Resources\ProductInTransitResource\Pages;

use App\Filament\Resources\ProductInTransitResource;
use App\Models\ProductInTransit;
use App\Models\ProductTemplate;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class CreateProductInTransit extends CreateRecord
{
    protected static string $resource = ProductInTransitResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $createdBy = Auth::id();

        $common = [
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'shipping_location' => $data['shipping_location'] ?? null,
            'shipping_date' => $data['shipping_date'] ?? now()->toDateString(),
            'transport_number' => $data['transport_number'] ?? null,
            'tracking_number' => $data['tracking_number'] ?? null,
            'expected_arrival_date' => $data['expected_arrival_date'] ?? null,
            'status' => $data['status'] ?? ProductInTransit::STATUS_IN_TRANSIT,
            'notes' => $data['notes'] ?? null,
            'document_path' => $data['document_path'] ?? null,
            'created_by' => $createdBy,
            'is_active' => true,
        ];

        $firstRecord = null;

        foreach ($data['items'] as $item) {
            $attributes = [];
            foreach ($item as $key => $value) {
                if (str_starts_with($key, 'attribute_') && $value !== null) {
                    $attributeName = str_replace('attribute_', '', $key);
                    $attributes[$attributeName] = $value;
                }
            }

            $recordData = array_merge($common, [
                'product_template_id' => $item['product_template_id'],
                'name' => $item['name'],
                'producer' => $item['producer'] ?? null,
                'quantity' => $item['quantity'] ?? 1,
                'attributes' => $attributes,
            ]);

            // Рассчет объема, если задана формула (аналогично Product)
            if (!empty($recordData['product_template_id'])) {
                $template = ProductTemplate::find($recordData['product_template_id']);
                if ($template && $template->formula) {
                    $attrsForFormula = is_array($attributes) ? $attributes : [];
                    $attrsForFormula['quantity'] = (int) ($recordData['quantity'] ?? 0);
                    $testResult = $template->testFormula($attrsForFormula);
                    if ($testResult['success']) {
                        $recordData['calculated_volume'] = (float) $testResult['result'];
                    }
                }
            }

            $created = ProductInTransit::create($recordData);
            if ($firstRecord === null) {
                $firstRecord = $created;
            }
        }

        return $firstRecord ?? new ProductInTransit();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 