<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

// use Throwable; // не требуется в новых версиях, т.к. FQCN указан в сигнатуре

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Добавляем заголовки безопасности для всех маршрутов
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'compress' => \App\Http\Middleware\CompressResponse::class,
            'filament-security' => \App\Http\Middleware\FilamentSecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, Request $request) {
            $isAdmin = $request->is('admin') || $request->is('admin/*');
            if (! $isAdmin) {
                return null;
            }

            $isForbidden = $e instanceof AuthorizationException
                || $e instanceof AccessDeniedHttpException
                || ($e instanceof HttpException && $e->getStatusCode() === 403);

            if ($isForbidden) {
                return response()->view('filament.errors.403', [
                    'message' => trim((string) $e->getMessage()) ?: 'У вас нет прав для выполнения этого действия.',
                ], 403);
            }

            return null;
        });
    })->create();
