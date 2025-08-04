<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Livewire\TestFormula;

class Product extends Model
{
    use HasFactory;

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
        'arrival_date',
        'is_active',
    ];

    protected $casts = [
        'attributes' => 'array',
        'calculated_volume' => 'decimal:4',
        'quantity' => 'integer',
        'arrival_date' => 'date',
        'is_active' => 'boolean',
    ];

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
     * Связь с продажами
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
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
            $testResult = $this->template->testFormula($this->attributes);
            
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
     * Проверить, есть ли остатки на складе
     */
    public function hasStock(): bool
    {
        return $this->quantity > 0 && $this->is_active;
    }

    /**
     * Уменьшить количество товара
     */
    public function decreaseQuantity(int $amount): bool
    {
        if ($this->quantity >= $amount) {
            $this->quantity -= $amount;
            $this->save();
            return true;
        }
        
        return false;
    }

    /**
     * Увеличить количество товара
     */
    public function increaseQuantity(int $amount): void
    {
        $this->quantity += $amount;
        $this->save();
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
} 