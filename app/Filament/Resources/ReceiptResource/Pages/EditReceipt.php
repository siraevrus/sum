<?php

namespace App\Filament\Resources\ReceiptResource\Pages;

use App\Filament\Resources\ReceiptResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReceipt extends EditRecord
{
    protected static string $resource = ReceiptResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Преобразуем attributes в поля для репитера товаров (один товар)
        $item = [
            'product_template_id' => $data['product_template_id'] ?? null,
            'producer_id' => $data['producer_id'] ?? null,
            'name' => $data['name'] ?? null,
            'quantity' => $data['quantity'] ?? 1,
            'calculated_volume' => $data['calculated_volume'] ?? null,
            'description' => $data['description'] ?? null,
            'actual_arrival_date' => $data['actual_arrival_date'] ?? null,
        ];
        if (isset($data['attributes']) && is_array($data['attributes'])) {
            foreach ($data['attributes'] as $key => $value) {
                $item["attribute_{$key}"] = $value;
            }
        }
        $data['products'] = [$item];
        return $data;
    }
}
