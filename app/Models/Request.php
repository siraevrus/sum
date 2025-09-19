<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class Request extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'warehouse_id',
        'product_template_id',
        'attributes',
        'calculated_volume',
        'title',
        'description',
        'quantity',
        'status',
        'admin_notes',
        'approved_by',
        'processed_by',
        'approved_at',
        'processed_at',
        'completed_at',
        'is_active',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'attributes' => 'array',
        'calculated_volume' => 'decimal:4',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Статусы запросов
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';


    /**
     * Связь с пользователем, создавшим запрос
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Связь со складом
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Связь с шаблоном товара
     */
    public function productTemplate(): BelongsTo
    {
        return $this->belongsTo(ProductTemplate::class);
    }

    /**
     * Связь с пользователем, одобрившим запрос
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Связь с пользователем, обработавшим запрос
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Связь с компанией через склад
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'warehouse_id', 'id');
    }

    /**
     * Получить статус на русском языке
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Ожидает рассмотрения',
            self::STATUS_APPROVED => 'Одобрен',
            default => 'Неизвестно',
        };
    }

    /**
     * Получить цвет статуса
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'info',
            default => 'gray',
        };
    }


    /**
     * Проверить, можно ли одобрить запрос
     */
    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->is_active;
    }

    /**
     * Проверить, можно ли отклонить запрос
     */
    public function canBeRejected(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->is_active;
    }

    /**
     * Одобрить запрос
     */
    public function approve(string $notes = null): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $this->status = self::STATUS_APPROVED;
        $this->approved_by = Auth::id();
        $this->approved_at = now();
        
        if ($notes) {
            $this->admin_notes = $notes;
        }
        
        $this->save();
        return true;
    }

    /**
     * Отклонить запрос
     */
    public function reject(string $notes = null): bool
    {
        if (!$this->canBeRejected()) {
            return false;
        }

        $this->status = self::STATUS_APPROVED; // В нашей системе "отклонение" = "одобрение" с заметками
        
        if ($notes) {
            $this->admin_notes = $notes;
        }
        
        $this->save();
        return true;
    }

    /**
     * Получить время обработки в днях
     */
    public function getProcessingDays(): int
    {
        if (!$this->approved_at) {
            return 0;
        }

        return $this->approved_at->diffInDays(now());
    }

    /**
     * Проверить, просрочен ли запрос (более 7 дней после одобрения)
     */
    public function isOverdue(): bool
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return false;
        }

        return $this->getProcessingDays() > 7;
    }

    /**
     * Scope для активных запросов
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
     * Scope для фильтрации по пользователю
     */
    public function scopeByUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    /**
     * Scope для просроченных запросов
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->where('status', self::STATUS_APPROVED)
              ->where('approved_at', '<', now()->subDays(7));
    }

    /**
     * Scope для ожидающих запросов
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Получить статистику по запросам
     */
    public static function getStats(): array
    {
        return [
            'total_requests' => static::count(),
            'pending_requests' => static::byStatus(self::STATUS_PENDING)->count(),
            'approved_requests' => static::byStatus(self::STATUS_APPROVED)->count(),
            'overdue_requests' => static::overdue()->count(),
        ];
    }
} 