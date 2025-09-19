<?php

namespace App\Casts;

use App\UserRole;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class UserRoleCast implements CastsAttributes
{
    /**
     * Cast the given value.
     */
    public function get($model, string $key, $value, array $attributes): UserRole
    {
        // Значение в БД хранится строкой
        return match ($value) {
            'admin' => UserRole::ADMIN,
            'operator' => UserRole::OPERATOR,
            'warehouse_worker', 'worker' => UserRole::WAREHOUSE_WORKER,
            'sales_manager' => UserRole::SALES_MANAGER,
            default => UserRole::OPERATOR,
        };
    }

    /**
     * Prepare the given value for storage.
     */
    public function set($model, string $key, $value, array $attributes): string
    {
        if ($value instanceof UserRole) {
            return $value->value;
        }

        $value = (string) $value;

        return match ($value) {
            'admin' => UserRole::ADMIN->value,
            'operator' => UserRole::OPERATOR->value,
            'warehouse_worker', 'worker' => UserRole::WAREHOUSE_WORKER->value,
            'sales_manager' => UserRole::SALES_MANAGER->value,
            default => UserRole::OPERATOR->value,
        };
    }
}
