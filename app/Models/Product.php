<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Product extends Model
{
    use HasFactory;

    // Статусы товара
    public const STATUS_IN_STOCK = 'in_stock';

    public const STATUS_IN_TRANSIT = 'in_transit';

    public const STATUS_FOR_RECEIPT = 'for_receipt';

    protected $fillable = [
        'product_template_id',
        'warehouse_id',
        'created_by',
        'name',
        'description',
        'attributes',
        'calculated_volume',
        'quantity',
        'sold_quantity',
        'transport_number',
        'producer',
        'arrival_date',
        'status',
        'is_active',
        'shipping_location',
        'shipping_date',
        'tracking_number',
        'expected_arrival_date',
        'actual_arrival_date',
        'document_path',
        'notes',
    ];

    protected $casts = [
        'attributes' => 'array',
        'calculated_volume' => 'decimal:4',
        'quantity' => 'integer',
        'sold_quantity' => 'integer',
        'arrival_date' => 'date',
        'status' => 'string',
        'is_active' => 'boolean',
        'shipping_date' => 'date',
        'expected_arrival_date' => 'date',
        'actual_arrival_date' => 'date',
        'document_path' => 'array',
    ];

    protected $attributes = [
        'attributes' => '[]',
    ];

    /**
     * Связь с шаблоном товара
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ProductTemplate::class, 'product_template_id');
    }

    /**
     * Связь с шаблоном товара (альтернативное название)
     */
    public function productTemplate(): BelongsTo
    {
        return $this->belongsTo(ProductTemplate::class, 'product_template_id');
    }

    /**
     * Связь со складом
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Связь с создателем
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Связь с компанией через склад
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'warehouse_id', 'id');
    }

    /**
     * Связь с продажами
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Связь с товаром в пути
     */
    public function inTransitRecord(): HasOne
    {
        return $this->hasOne(\App\Models\ProductInTransit::class, 'product_template_id', 'product_template_id')
            ->where('warehouse_id', $this->warehouse_id)
            ->where('name', $this->name)
            ->where('producer', $this->producer)
            ->where('status', \App\Models\ProductInTransit::STATUS_IN_TRANSIT);
    }

    /**
     * Получить значение характеристики товара
     */
    public function getProductAttributeValue(string $attributeName): mixed
    {
        return $this->attributes[$attributeName] ?? null;
    }

    /**
     * Установить значение характеристики товара
     */
    public function setAttributeValue(string $attributeName, mixed $value): void
    {
        $attributes = $this->attributes ?? [];
        $attributes[$attributeName] = $value;
        $this->attributes = $attributes;
    }

    /**
     * Рассчитать объем товара на основе формулы шаблона
     */
    public function calculateVolume(): ?float
    {
        // Загружаем productTemplate, если не загружен
        if (! $this->relationLoaded('productTemplate')) {
            $this->load('productTemplate');
        }

        if (! $this->productTemplate || ! $this->productTemplate->formula) {
            return null;
        }

        try {
            $attributes = $this->getAttribute('attributes');
            if (is_string($attributes)) {
                $attributes = json_decode($attributes, true) ?? [];
            } elseif (! is_array($attributes)) {
                $attributes = [];
            }

            // Используем только характеристики для формулы (без количества)

            $testResult = $this->productTemplate->testFormula($attributes);

            if ($testResult['success']) {
                $result = (float) $testResult['result'];

                // Ограничиваем максимальное значение объема до 99999 (5 символов)
                if ($result > 99999) {
                    $result = 99999;
                }

                return $result;
            }

            // Отладочная информация
            \Illuminate\Support\Facades\Log::info('calculateVolume failed', [
                'product_id' => $this->id,
                'template_id' => $this->product_template_id,
                'formula' => $this->productTemplate->formula,
                'attributes' => $attributes,
                'test_result' => $testResult,
            ]);

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Обновить рассчитанный объем
     */
    public function updateCalculatedVolume(): void
    {
        $volume = $this->calculateVolume();

        // Ограничиваем максимальное значение объема до 99999 (5 символов)
        if ($volume !== null && $volume > 99999) {
            $volume = 99999;
        }

        $this->calculated_volume = $volume;
        $this->save();
    }

    /**
     * Получить общий объем (количество * рассчитанный объем)
     */
    public function getTotalVolume(): ?float
    {
        if ($this->calculated_volume === null) {
            return null;
        }

        return $this->calculated_volume * $this->quantity;
    }

    /**
     * Получить характеристики товара в читаемом виде
     */
    public function getAttributesText(): string
    {
        if (empty($this->attributes)) {
            return '';
        }

        $parts = [];
        foreach ($this->attributes as $key => $value) {
            if ($value !== null && $value !== '') {
                $parts[] = "{$key}: {$value}";
            }
        }

        return implode(', ', $parts);
    }

    /**
     * Получить полное название товара с производителем
     */
    public function getFullName(): string
    {
        $name = $this->name;

        if ($this->producer) {
            $name .= ' ('.$this->producer.')';
        }

        return $name;
    }

    /**
     * Проверить, есть ли остатки на складе
     */
    public function hasStock(): bool
    {
        return $this->getAvailableQuantity() > 0 && $this->is_active;
    }

    /**
     * Получить доступное количество (остатки)
     */
    public function getAvailableQuantity(): int
    {
        return $this->quantity - $this->sold_quantity;
    }

    /**
     * Уменьшить количество товара (при продаже)
     */
    public function decreaseQuantity(int $amount): bool
    {
        if ($this->quantity >= $amount) {
            // Увеличиваем проданное количество вместо уменьшения исходного
            $this->sold_quantity += $amount;
            $this->save();

            return true;
        }

        return false;
    }

    /**
     * Увеличить количество товара (при отмене продажи)
     */
    public function increaseQuantity(int $amount): void
    {
        // Уменьшаем проданное количество при отмене продажи
        if ($this->sold_quantity >= $amount) {
            $this->sold_quantity -= $amount;
            $this->save();
        }
    }

    /**
     * Scope для активных товаров
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope для товаров с остатками
     */
    public function scopeInStock(Builder $query): void
    {
        $query->where('quantity', '>', 0);
    }

    /**
     * Scope для фильтрации по складу
     */
    public function scopeByWarehouse(Builder $query, int $warehouseId): void
    {
        $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope для фильтрации по производителю
     */
    public function scopeByProducer(Builder $query, string $producer): void
    {
        $query->where('producer', $producer);
    }

    /**
     * Scope для фильтрации по шаблону
     */
    public function scopeByTemplate(Builder $query, int $templateId): void
    {
        $query->where('product_template_id', $templateId);
    }

    /**
     * Получить список производителей
     */
    public static function getProducers(): array
    {
        return static::distinct()
            ->whereNotNull('producer')
            ->pluck('producer')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Получить статистику по товарам
     */
    public static function getStats(): array
    {
        return [
            'total_products' => static::count(),
            'active_products' => static::active()->count(),
            'products_in_stock' => static::inStock()->count(),
            'total_quantity' => static::sum('quantity'),
            'total_volume' => static::sum('calculated_volume'),
        ];
    }

    /**
     * Получить сгруппированные остатки товаров
     */
    public static function getGroupedStock(?Builder $query = null): \Illuminate\Database\Eloquent\Collection
    {
        $baseQuery = $query ?? static::query();

        return $baseQuery
            ->select([
                'product_template_id',
                'warehouse_id',
                'producer',
                'attributes',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(calculated_volume) as total_volume'),
                DB::raw('COUNT(*) as product_count'),
                DB::raw('MIN(name) as name'),
                DB::raw('MIN(description) as description'),
                DB::raw('MIN(arrival_date) as first_arrival_date'),
                DB::raw('MAX(arrival_date) as last_arrival_date'),
            ])
            ->groupBy(['product_template_id', 'warehouse_id', 'producer', 'attributes'])
            ->having('total_quantity', '>', 0)
            ->orderBy('total_quantity', 'desc')
            ->get();
    }

    /**
     * Получить уникальный ключ для группировки
     */
    public function getGroupingKey(): string
    {
        $attributes = $this->attributes ?? [];
        ksort($attributes); // Сортируем атрибуты для консистентности

        return md5(
            $this->product_template_id.'|'.
            $this->warehouse_id.'|'.
            $this->producer.'|'.
            json_encode($attributes)
        );
    }

    public function isInStock(): bool
    {
        return $this->status === self::STATUS_IN_STOCK;
    }

    public function isInTransit(): bool
    {
        return $this->status === self::STATUS_IN_TRANSIT;
    }

    public function isForReceipt(): bool
    {
        return $this->status === self::STATUS_FOR_RECEIPT;
    }

    public function markInStock(): void
    {
        $this->status = self::STATUS_IN_STOCK;
        $this->arrival_date = $this->arrival_date ?? now();
        $this->save();

        // Удаляем запись из таблицы товаров в пути
        $this->removeFromTransitRecord();
    }

    /**
     * Удалить запись из таблицы товаров в пути
     */
    private function removeFromTransitRecord(): void
    {
        try {
            \App\Models\ProductInTransit::where([
                'product_template_id' => $this->product_template_id,
                'warehouse_id' => $this->warehouse_id,
                'name' => $this->name,
                'producer' => $this->producer,
                'status' => \App\Models\ProductInTransit::STATUS_IN_TRANSIT,
            ])->delete();
        } catch (\Exception $e) {
            // Логируем ошибку, но не прерываем выполнение
            Log::error('Ошибка при удалении записи из пути: '.$e->getMessage(), [
                'product_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function markInTransit(): void
    {
        $this->status = self::STATUS_IN_TRANSIT;
        $this->save();

        // Создаем запись в таблице товаров в пути
        $this->createInTransitRecord();
    }

    public function markForReceipt(): void
    {
        $this->status = self::STATUS_FOR_RECEIPT;
        $this->save();
    }

    /**
     * Создать запись в таблице товаров в пути
     */
    private function createInTransitRecord(): void
    {
        try {
            // Проверяем, нет ли уже записи в пути для этого товара
            $existingRecord = \App\Models\ProductInTransit::where([
                'product_template_id' => $this->product_template_id,
                'warehouse_id' => $this->warehouse_id,
                'name' => $this->name,
                'producer' => $this->producer,
                'status' => \App\Models\ProductInTransit::STATUS_IN_TRANSIT,
            ])->first();

            if ($existingRecord) {
                // Обновляем существующую запись
                $existingRecord->update([
                    'quantity' => $this->quantity,
                    'calculated_volume' => $this->calculated_volume,
                    'attributes' => $this->attributes,
                    'shipping_date' => $this->shipping_date ?? now(),
                    'expected_arrival_date' => $this->expected_arrival_date ?? now()->addDays(7),
                ]);

                return;
            }

            // Создаем новую запись
            \App\Models\ProductInTransit::create([
                'product_template_id' => $this->product_template_id,
                'warehouse_id' => $this->warehouse_id,
                'created_by' => $this->created_by,
                'name' => $this->name,
                'description' => $this->description,
                'attributes' => $this->attributes,
                'calculated_volume' => $this->calculated_volume,
                'quantity' => $this->quantity,
                'producer' => $this->producer,
                'shipping_location' => $this->shipping_location ?? 'Склад',
                'shipping_date' => $this->shipping_date ?? now(),
                'transport_number' => $this->transport_number,
                'tracking_number' => $this->tracking_number,
                'expected_arrival_date' => $this->expected_arrival_date ?? now()->addDays(7),
                'status' => \App\Models\ProductInTransit::STATUS_IN_TRANSIT,
                'notes' => $this->notes,
                'document_path' => $this->document_path,
                'is_active' => true,
            ]);
        } catch (\Exception $e) {
            // Логируем ошибку, но не прерываем выполнение
            Log::error('Ошибка при создании записи в пути: '.$e->getMessage(), [
                'product_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
