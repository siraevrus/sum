<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'legal_address',
        'postal_address',
        'phone_fax',
        'general_director',
        'email',
        'inn',
        'kpp',
        'ogrn',
        'bank',
        'account_number',
        'correspondent_account',
        'bik',
        'employees_count',
        'warehouses_count',
        'is_archived',
        'archived_at',
    ];

    protected $casts = [
        'employees_count' => 'integer',
        'warehouses_count' => 'integer',
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    /**
     * Get the warehouses for the company.
     */
    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    /**
     * Get the employees for the company.
     */
    public function employees(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the dynamic employees count for the company.
     */
    public function getDynamicEmployeesCountAttribute(): int
    {
        return $this->employees()->count();
    }

    /**
     * Get the dynamic warehouses count for the company.
     */
    public function getDynamicWarehousesCountAttribute(): int
    {
        return $this->warehouses()->count();
    }

    /**
     * Scope a query to only include active companies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Archive the company.
     */
    public function archive(): void
    {
        $this->update([
            'is_archived' => true,
            'archived_at' => now(),
        ]);
    }

    /**
     * Restore the company.
     */
    public function restore(): void
    {
        $this->update([
            'is_archived' => false,
            'archived_at' => null,
        ]);
    }

    /**
     * Update employees count
     */
    public function updateEmployeesCount(): void
    {
        $this->update(['employees_count' => $this->employees()->count()]);
    }

    /**
     * Update warehouses count
     */
    public function updateWarehousesCount(): void
    {
        $this->update(['warehouses_count' => $this->warehouses()->count()]);
    }

    /**
     * Update both counts
     */
    public function updateCounts(): void
    {
        $this->updateEmployeesCount();
        $this->updateWarehousesCount();
    }

    /**
     * Prevent deleting company when it still has related warehouses or employees.
     */
    protected static function booted(): void
    {
        static::deleting(function (Company $company): void {
            if ($company->warehouses()->exists() || $company->employees()->exists()) {
                throw new \RuntimeException('Нельзя удалить компанию, у которой есть склады или сотрудники. Архивируйте компанию или удалите связанные записи.');
            }
        });
    }

}
