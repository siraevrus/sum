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
        
        // Рассчитываем цену за единицу
        $data['unit_price'] = $data['total_price'] / ($data['quantity'] ?? 1);
        
        // Рассчитываем цену без НДС
        $data['price_without_vat'] = $data['total_price'] / 1.2; // НДС 20%
        
        // Рассчитываем сумму НДС
        $data['vat_amount'] = $data['total_price'] - $data['price_without_vat'];
        
        // Устанавливаем значения по умолчанию
        $data['vat_rate'] = $data['vat_rate'] ?? 20.00;
        $data['currency'] = $data['currency'] ?? 'RUB';
        $data['exchange_rate'] = $data['exchange_rate'] ?? 1.0000;
        $data['payment_status'] = $data['payment_status'] ?? 'pending';
        $data['delivery_status'] = $data['delivery_status'] ?? 'pending';
        $data['is_active'] = $data['is_active'] ?? true;
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Списываем товар со склада после создания продажи
        $sale = $this->record;
        if ($sale->product && $sale->quantity > 0) {
            $success = $sale->product->decreaseQuantity($sale->quantity);
            
            if (!$success) {
                // Если не удалось списать товар, показываем ошибку
                $this->notify('error', 'Недостаточно товара на складе для продажи');
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
} 