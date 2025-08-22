<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\UserRole;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Все аутентифицированные пользователи могут видеть список
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Product $product): bool
    {
        // Администратор может видеть все товары
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        // Остальные пользователи могут видеть только товары на своем складе
        return (int) $product->warehouse_id === (int) $user->warehouse_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::ADMIN, UserRole::OPERATOR, UserRole::WAREHOUSE_WORKER]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Product $product): bool
    {
        // Администратор может обновлять все товары
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        // Остальные пользователи могут обновлять только товары на своем складе
        return (int) $product->warehouse_id === (int) $user->warehouse_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Product $product): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return $user->role === UserRole::ADMIN;
    }
}
