<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Livewire\TestFormula;

class ProductInTransit extends Model
{
    use HasFactory;

    protected $table = 'product_in_transit';

    protected $fillable = [
        'product_template_id',
        'warehouse_id',
        'created_by',
        'name',
        'description',
        'attributes',
        'calculated_volume',
        'quantity',
        'transport_number',
        'producer',
        'shipping_location',
        'shipping_date',
        'tracking_number',
        'expected_arrival_date',
        'actual_arrival_date',
        'status',
        'notes',
        'document_path',
        'is_active',
    ];

    protected $casts = [
        'attributes' => 'array',
        'document_path' => 'array',
        'calculated_volume' => 'decimal:4',
        'quantity' => 'integer',
        'expected_arrival_date' => 'date',
        'actual_arrival_date' => 'date',
        'shipping_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Статусы товаров в пути
    const STATUS_ORDERED = 'ordered';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_ARRIVED = 'arrived';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Связь с шаблоном товара
     */
    public function template(): BelongsTo
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
        if (!$this->template || !$this->template->formula) {
            return null;
        }

        try {
            // Добавляем количество в атрибуты для использования в формуле
            $attributes = $this->attributes;
            $attributes['quantity'] = $this->quantity;
            
            $testResult = $this->template->testFormula($attributes);
            
            if ($testResult['success']) {
                return (float) $testResult['result'];
            }
            
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
        $this->calculated_volume = $this->calculateVolume();
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
     * Получить полное название товара с производителем
     */
    public function getFullName(): string
    {
        $name = $this->name;
        
        if ($this->producer) {
            $name .= ' (' . $this->producer . ')';
        }
        
        return $name;
    }

    /**
     * Проверить, можно ли принять товар
     */
    public function canBeReceived(): bool
    {
        return in_array($this->status, [self::STATUS_ARRIVED, self::STATUS_IN_TRANSIT]) && $this->is_active;
    }

    /**
     * Принять товар (перевести в остатки)
     */
    public function receive(): bool
    {
        if (!$this->canBeReceived()) {
            return false;
        }

        // Создаем товар в остатках
        $product = Product::create([
            'product_template_id' => $this->product_template_id,
            'warehouse_id' => $this->warehouse_id,
            'created_by' => $this->created_by,
            'name' => $this->name,
            'description' => $this->description,
            'attributes' => $this->attributes,
            'calculated_volume' => $this->calculated_volume,
            'quantity' => $this->quantity,
            'transport_number' => $this->transport_number,
            'producer' => $this->producer,
            'arrival_date' => $this->actual_arrival_date ?? now(),
            'is_active' => true,
        ]);

        if ($product) {
            $this->status = self::STATUS_RECEIVED;
            $this->actual_arrival_date = now();
            $this->save();
            return true;
        }

        return false;
    }

    /**
     * Обновить статус
     */
    public function updateStatus(string $status): bool
    {
        if (in_array($status, [self::STATUS_ORDERED, self::STATUS_IN_TRANSIT, self::STATUS_ARRIVED, self::STATUS_RECEIVED, self::STATUS_CANCELLED])) {
            $this->status = $status;
            
            if ($status === self::STATUS_ARRIVED) {
                $this->actual_arrival_date = now();
            }
            
            $this->save();
            return true;
        }
        
        return false;
    }

    /**
     * Получить статус на русском языке
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_ORDERED => 'Заказан',
            self::STATUS_IN_TRANSIT => 'В пути',
            self::STATUS_ARRIVED => 'Прибыл',
            self::STATUS_RECEIVED => 'Принят',
            self::STATUS_CANCELLED => 'Отменен',
            default => 'Неизвестно',
        };
    }

    /**
     * Получить цвет статуса
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_ORDERED => 'warning',
            self::STATUS_IN_TRANSIT => 'info',
            self::STATUS_ARRIVED => 'success',
            self::STATUS_RECEIVED => 'success',
            self::STATUS_CANCELLED => 'danger',
            default => 'gray',
        };
    }

    /**
     * Проверить, просрочена ли доставка
     */
    public function isOverdue(): bool
    {
        return $this->expected_arrival_date < now() && !in_array($this->status, [self::STATUS_RECEIVED, self::STATUS_CANCELLED]);
    }

    /**
     * Получить количество дней до прибытия
     */
    public function getDaysUntilArrival(): int
    {
        return now()->diffInDays($this->expected_arrival_date, false);
    }

    /**
     * Scope для активных записей
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Scope для фильтрации по статусу
     */
    public function scopeByStatus(Builder $query, string $status): void
    {
        $query->where('status', $status);
    }

    /**
     * Scope для фильтрации по складу
     */
    public function scopeByWarehouse(Builder $query, int $warehouseId): void
    {
        $query->where('warehouse_id', $warehouseId);
    }

    /**
     * Scope для фильтрации по месту отгрузки
     */
    public function scopeByShippingLocation(Builder $query, string $location): void
    {
        $query->where('shipping_location', $location);
    }

    /**
     * Scope для просроченных доставок
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->where('expected_arrival_date', '<', now())
              ->whereNotIn('status', [self::STATUS_RECEIVED, self::STATUS_CANCELLED]);
    }

    /**
     * Scope для товаров в пути
     */
    public function scopeInTransit(Builder $query): void
    {
        $query->whereIn('status', [self::STATUS_ORDERED, self::STATUS_IN_TRANSIT, self::STATUS_ARRIVED]);
    }

    /**
     * Получить список мест отгрузки
     */
    public static function getShippingLocations(): array
    {
        return static::distinct()
            ->whereNotNull('shipping_location')
            ->pluck('shipping_location')
            ->filter()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Получить статистику по товарам в пути
     */
    public static function getStats(): array
    {
        return [
            'total_in_transit' => static::inTransit()->count(),
            'ordered' => static::byStatus(self::STATUS_ORDERED)->count(),
            'in_transit' => static::byStatus(self::STATUS_IN_TRANSIT)->count(),
            'arrived' => static::byStatus(self::STATUS_ARRIVED)->count(),
            'overdue' => static::overdue()->count(),
            'total_quantity' => static::inTransit()->sum('quantity'),
            'total_volume' => static::inTransit()->sum('calculated_volume'),
        ];
    }
} 