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
        'title',
        'description',
        'quantity',
        'priority',
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
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Статусы запросов
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    // Приоритеты запросов
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

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
            self::STATUS_REJECTED => 'Отклонен',
            self::STATUS_IN_PROGRESS => 'В обработке',
            self::STATUS_COMPLETED => 'Завершен',
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
            self::STATUS_PENDING => 'warning',
            self::STATUS_APPROVED => 'info',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_IN_PROGRESS => 'primary',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_CANCELLED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Получить приоритет на русском языке
     */
    public function getPriorityLabel(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'Низкий',
            self::PRIORITY_NORMAL => 'Обычный',
            self::PRIORITY_HIGH => 'Высокий',
            self::PRIORITY_URGENT => 'Срочный',
            default => 'Неизвестно',
        };
    }

    /**
     * Получить цвет приоритета
     */
    public function getPriorityColor(): string
    {
        return match($this->priority) {
            self::PRIORITY_LOW => 'gray',
            self::PRIORITY_NORMAL => 'info',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_URGENT => 'danger',
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
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED]) && $this->is_active;
    }

    /**
     * Проверить, можно ли начать обработку
     */
    public function canBeProcessed(): bool
    {
        return $this->status === self::STATUS_APPROVED && $this->is_active;
    }

    /**
     * Проверить, можно ли завершить запрос
     */
    public function canBeCompleted(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS && $this->is_active;
    }

    /**
     * Проверить, можно ли отменить запрос
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED]) && $this->is_active;
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

        $this->status = self::STATUS_REJECTED;
        
        if ($notes) {
            $this->admin_notes = $notes;
        }
        
        $this->save();
        return true;
    }

    /**
     * Начать обработку запроса
     */
    public function startProcessing(): bool
    {
        if (!$this->canBeProcessed()) {
            return false;
        }

        $this->status = self::STATUS_IN_PROGRESS;
        $this->processed_by = Auth::id();
        $this->processed_at = now();
        $this->save();
        
        return true;
    }

    /**
     * Завершить запрос
     */
    public function complete(): bool
    {
        if (!$this->canBeCompleted()) {
            return false;
        }

        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        $this->save();
        
        return true;
    }

    /**
     * Отменить запрос
     */
    public function cancel(string $notes = null): bool
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        $this->status = self::STATUS_CANCELLED;
        
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
        if (!$this->processed_at) {
            return 0;
        }

        $endDate = $this->completed_at ?? now();
        return $this->processed_at->diffInDays($endDate);
    }

    /**
     * Проверить, просрочен ли запрос (более 7 дней в обработке)
     */
    public function isOverdue(): bool
    {
        if ($this->status !== self::STATUS_IN_PROGRESS) {
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
     * Scope для фильтрации по приоритету
     */
    public function scopeByPriority(Builder $query, string $priority): void
    {
        $query->where('priority', $priority);
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
        $query->where('status', self::STATUS_IN_PROGRESS)
              ->where('processed_at', '<', now()->subDays(7));
    }

    /**
     * Scope для ожидающих запросов
     */
    public function scopePending(Builder $query): void
    {
        $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED]);
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
            'in_progress_requests' => static::byStatus(self::STATUS_IN_PROGRESS)->count(),
            'completed_requests' => static::byStatus(self::STATUS_COMPLETED)->count(),
            'overdue_requests' => static::overdue()->count(),
            'urgent_requests' => static::byPriority(self::PRIORITY_URGENT)->count(),
        ];
    }
} 