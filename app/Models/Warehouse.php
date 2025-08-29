<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'company_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the company that owns the warehouse.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the employees for the warehouse.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the products for the warehouse.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the employees count for the warehouse.
     */
    public function getEmployeesCountAttribute(): int
    {
        return $this->employees()->count();
    }

    /**
     * Scope a query to only include active warehouses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the full address with company name.
     */
    public function getFullAddressAttribute(): string
    {
        return $this->company ? "{$this->company->name}, {$this->address}" : $this->address;
    }

    /**
     * Список складов, доступных пользователю, для выпадающих списков (id => name)
     */
    public static function optionsForUser(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $query = static::query()
            ->active()
            ->whereHas('company', function (Builder $query) {
                $query->where('is_archived', false);
            });

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return $query->pluck('name', 'id')->toArray();
        }

        if (! empty($user->warehouse_id)) {
            $query->where('id', $user->warehouse_id);
        } else {
            // если нет привязки к складу — не показываем ничего
            return [];
        }

        return $query->pluck('name', 'id')->toArray();
    }

    /**
     * Список складов для текущего аутентифицированного пользователя
     */
    public static function optionsForCurrentUser(): array
    {
        return static::optionsForUser(Auth::user());
    }
}
