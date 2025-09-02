<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producer extends Model
{
    /** @use HasFactory<\Database\Factories\ProducerFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Получить все товары этого производителя
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Получить название производителя
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
