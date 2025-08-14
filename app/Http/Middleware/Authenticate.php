<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo($request)
    {
        // Для API не выполняем редирект — отдадим 401 (обрабатывается Handler::unauthenticated)
        if ($request->is('api') || $request->is('api/*')) {
            return null;
        }

        // Для админки Filament ведем на страницу логина админки
        return '/admin/login';
    }
}


