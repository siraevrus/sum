<?php

namespace App\Http\Middleware;

use App\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! $request->user()) {
            if ($request->is('api') || $request->is('api/*')) {
                return response()->json(['message' => 'Не авторизован'], 401);
            }

            return redirect('/admin/login');
        }

        if ($request->user()->isBlocked()) {
            \Illuminate\Support\Facades\Auth::logout();
            if ($request->is('api') || $request->is('api/*')) {
                return response()->json(['message' => 'Ваш аккаунт заблокирован'], 401);
            }

            return redirect('/admin/login')->withErrors([
                'email' => 'Ваш аккаунт заблокирован',
            ]);
        }

        $userRole = $request->user()->role;

        // Администратор имеет доступ ко всему
        if ($userRole === UserRole::ADMIN) {
            return $next($request);
        }

        // Проверяем конкретную роль
        if ($userRole->value === $role) {
            return $next($request);
        }

        // Проверяем разрешения
        if ($request->user()->hasPermission($role)) {
            return $next($request);
        }

        abort(403, 'Доступ запрещен');
    }
}
