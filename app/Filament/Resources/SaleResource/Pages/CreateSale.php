<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CreateSale extends CreateRecord
{
    protected static string $resource = SaleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Log::info('Начало создания продажи', $data);
        $data['user_id'] = Auth::id();
        $data['sale_number'] = \App\Models\Sale::generateSaleNumber();
        
        // warehouse_id уже выбран пользователем в форме
        
        // Обрабатываем составной ключ товара
        if (isset($data['product_id']) && str_contains($data['product_id'], '|')) {
            $parts = explode('|', $data['product_id']);
            if (count($parts) >= 4) {
                $productTemplateId = $parts[0];
                $warehouseId = $parts[1];
                $producer = $parts[2];
                $name = base64_decode($parts[3]);
                
                // Находим конкретный товар для списания
                $product = \App\Models\Product::where('product_template_id', $productTemplateId)
                    ->where('warehouse_id', $warehouseId)
                    ->where('producer', $producer)
                    ->where('name', $name)
                    ->where('quantity', '>', 0)
                    ->first();
                
                if ($product) {
                    $data['product_id'] = $product->id;
                    Log::info('Найден товар для продажи', ['product_id' => $product->id]);
                } else {
                    throw new \Exception('Товар не найден или отсутствует на складе');
                }
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
        // Продажа создана, но количество товара НЕ списывается из Product
        // Это позволяет сохранить историю добавления товаров
        // Остатки рассчитываются с учетом продаж в StockResource
        Log::info('Продажа создана успешно', ['sale_id' => $this->record->id]);
    }

    protected function getRedirectUrl(): string
    {
        Log::info('Редирект после создания продажи');
        return $this->getResource()::getUrl('index');
    }
} 