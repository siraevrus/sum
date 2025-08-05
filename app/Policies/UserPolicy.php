<?php

namespace App\Policies;

use App\Models\User;
use App\UserRole;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
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
    public function view(User $user, User $model): bool
    {
        // Администратор может видеть всех пользователей
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        // Остальные пользователи могут видеть только пользователей своей компании
        return $user->company_id === $model->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Администратор может обновлять всех пользователей
        if ($user->role === UserRole::ADMIN) {
            return true;
        }

        // Пользователь может обновлять свой профиль
        if ($user->id === $model->id) {
            return true;
        }

        // Остальные пользователи могут обновлять только пользователей своей компании
        return $user->company_id === $model->company_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        // Администратор может удалять всех пользователей, кроме себя
        if ($user->role === UserRole::ADMIN && $user->id !== $model->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->role === UserRole::ADMIN;
    }
}
