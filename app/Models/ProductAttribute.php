<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_template_id',
        'name',
        'variable',
        'type',
        'options',
        'unit',
        'is_required',
        'is_in_formula',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_in_formula' => 'boolean',
        'options' => 'array',
        'sort_order' => 'integer',
    ];

    /**
     * Fill the model with an array of attributes.
     * Переопределяем для защиты от UUID в sort_order
     */
    public function fill(array $attributes)
    {
        // Удаляем sort_order из атрибутов, если он есть
        unset($attributes['sort_order']);
        
        return parent::fill($attributes);
    }

    /**
     * Get the template that owns the attribute.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ProductTemplate::class, 'product_template_id');
    }

    /**
     * Get the options for select type.
     */
    public function getOptionsArrayAttribute(): array
    {
        if ($this->type !== 'select' || !$this->options) {
            return [];
        }

        $options = is_array($this->options) ? $this->options : explode(',', $this->options);
        // Возвращаем массив вида ['Россия' => 'Россия', ...]
        $assoc = [];
        foreach ($options as $option) {
            $assoc[$option] = $option;
        }
        return $assoc;
    }

    /**
     * Get the full name with unit.
     */
    public function getFullNameAttribute(): string
    {
        $name = $this->name;
        if ($this->unit) {
            $name .= " ({$this->unit})";
        }
        return $name;
    }

    /**
     * Scope a query to only include formula attributes.
     */
    public function scopeFormula($query)
    {
        return $query->where('is_in_formula', true);
    }

    /**
     * Scope a query to only include required attributes.
     */
    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /**
     * Get available types.
     */
    public static function getAvailableTypes(): array
    {
        return [
            'number' => 'Число',
            'text' => 'Текст',
            'select' => 'Выпадающий список',
        ];
    }
}
