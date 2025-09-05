<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            // Сохраняем оригинальный составной ключ
            $data['composite_product_key'] = $data['product_id'];

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
        $data['is_active'] = $data['is_active'] ?? true;

        return $data;
    }

    protected function afterCreate(): void
    {
        // Автоматически списываем товар сразу после создания продажи
        $sale = $this->record;

        DB::transaction(function () use ($sale) {
            // Получаем параметры товара из выбранной записи
            $product = $sale->product;
            if (! $product) {
                throw new \Exception('Не удалось найти товар для списания');
            }

            $templateId = $product->product_template_id;
            $warehouseId = $product->warehouse_id;
            $producerId = $product->producer_id; // Используем producer_id
            $name = $product->name;

            $remaining = (int) $sale->quantity;

            // Ищем все подходящие записи товара
            $candidates = \App\Models\Product::query()
                ->where('product_template_id', $templateId)
                ->where('warehouse_id', $warehouseId)
                ->where('producer_id', $producerId) // Используем producer_id
                ->where('name', $name)
                ->orderBy('id') // Сортируем по ID для предсказуемости
                ->lockForUpdate()
                ->get();

            // Проверяем общее доступное количество (quantity - sold_quantity)
            $totalAvailable = $candidates->sum(function ($product) {
                return $product->quantity - ($product->sold_quantity ?? 0);
            });
            if ($totalAvailable < $remaining) {
                throw new \Exception("Недостаточно остатка для списания. Доступно: {$totalAvailable}, требуется: {$remaining}");
            }

            // Распределяем продажу равномерно между всеми позициями
            foreach ($candidates as $candidate) {
                if ($remaining <= 0) {
                    break;
                }

                // Получаем доступное количество в этой позиции
                $availableInPosition = $candidate->quantity - ($candidate->sold_quantity ?? 0);
                
                if ($availableInPosition <= 0) {
                    continue; // Пропускаем позиции без доступного товара
                }

                // Списываем с позиции (либо все доступное, либо оставшееся количество)
                $decrement = min($remaining, $availableInPosition);
                
                if ($decrement <= 0) {
                    continue;
                }

                // Списываем с позиции
                $candidate->decreaseQuantity($decrement);
                $remaining -= $decrement;

                Log::info('Списание с позиции', [
                    'product_id' => $candidate->id,
                    'quantity' => $candidate->quantity,
                    'sold_quantity_before' => $candidate->sold_quantity ?? 0,
                    'available_before' => $availableInPosition,
                    'decrement' => $decrement,
                    'remaining' => $remaining
                ]);
            }

            if ($remaining > 0) {
                throw new \Exception('Недостаточно остатка для списания');
            }

            // Обновляем статусы продажи
            $sale->payment_status = \App\Models\Sale::PAYMENT_STATUS_PAID;
            $sale->save();
        });

        Log::info('Продажа создана и товар списан', ['sale_id' => $sale->id]);
    }

    protected function getRedirectUrl(): string
    {
        Log::info('Редирект после создания продажи');

        return $this->getResource()::getUrl('index');
    }
}
