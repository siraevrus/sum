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
        return $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : false;
    }
}


