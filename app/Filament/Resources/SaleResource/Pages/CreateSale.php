<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        $data['sale_number'] = \App\Models\Sale::generateSaleNumber();
        
        // Получаем warehouse_id из товара
        if (isset($data['product_id'])) {
            $product = \App\Models\Product::find($data['product_id']);
            if ($product) {
                $data['warehouse_id'] = $product->warehouse_id;
            }
        }
        
        // Рассчитываем общую сумму
        $data['total_price'] = ($data['cash_amount'] ?? 0) + ($data['nocash_amount'] ?? 0);
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 