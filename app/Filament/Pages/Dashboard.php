<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'Инфопанель';

    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        return $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : false;
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        if (!($user && method_exists($user, 'isAdmin') && $user->isAdmin())) {
            // Если не админ — отправляем на первую доступную страницу (Остатки)
            // Возврат false не всегда прерывает доступ, поэтому делаем явный redirect
            if (request()->expectsJson()) {
                abort(403, 'У вас нет прав для доступа к дашборду.');
            }
            return false;
        }
        return true;
    }
}


