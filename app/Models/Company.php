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
        'is_archived',
        'archived_at',
    ];

    protected $casts = [
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
     * Get the employees count for the company.
     */
    public function getEmployeesCountAttribute(): int
    {
        return $this->employees()->count();
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
}
