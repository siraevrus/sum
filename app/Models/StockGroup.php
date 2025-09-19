<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockGroup extends Model
{
    protected $table = 'products'; // Используем ту же таблицу, но с группировкой

    protected $fillable = [
        'product_template_id',
        'warehouse_id',
        'producer',
        'attributes',
        'total_quantity',
        'total_volume',
        'product_count',
        'name',
        'description',
        'first_arrival_date',
        'last_arrival_date',
    ];

    protected $casts = [
        'attributes' => 'array',
        'total_quantity' => 'integer',
        'total_volume' => 'decimal:4',
        'product_count' => 'integer',
        'first_arrival_date' => 'date',
        'last_arrival_date' => 'date',
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
     * Получить полное название с характеристиками
     */
    public function getFullName(): string
    {
        $name = $this->name ?? '';
        $attributesText = $this->getAttributesText();

        if ($attributesText) {
            $name .= " ({$attributesText})";
        }

        return $name;
    }

    /**
     * Получить цвет для количества
     */
    public function getQuantityColor(): string
    {
        if ($this->total_quantity > 10) {
            return 'success';
        }
        if ($this->total_quantity > 0) {
            return 'warning';
        }

        return 'danger';
    }

    /**
     * Получить информацию о последнем поступлении
     */
    public function getLastArrivalInfo(): string
    {
        if (! $this->last_arrival_date) {
            return 'Нет данных';
        }

        $daysAgo = now()->diffInDays($this->last_arrival_date);
        if ($daysAgo === 0) {
            return 'Сегодня';
        } elseif ($daysAgo === 1) {
            return 'Вчера';
        } else {
            return "{$daysAgo} дн. назад";
        }
    }
}
