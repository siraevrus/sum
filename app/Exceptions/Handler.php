<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $levels = [
        //
    ];

    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        //
    }

    public function render($request, Throwable $e)
    {
        // Для API всегда отдаем JSON при ошибках валидации
        if (($request->is('api') || $request->is('api/*')) && $e instanceof ValidationException) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $e->errors(),
            ], 422);
        }

        // Обработка 404 ошибок
        if ($e instanceof HttpException && $e->getStatusCode() === 404) {
            return response()->view('errors.404', [], 404);
        }

        $isAdmin = $request->is('admin') || $request->is('admin/*');

        if ($isAdmin) {
            $isForbidden = $e instanceof AuthorizationException
                || $e instanceof AccessDeniedHttpException
                || ($e instanceof HttpException && $e->getStatusCode() === 403);

            if ($isForbidden) {
                return response()->view('filament.errors.403', [
                    'message' => trim((string) $e->getMessage()) ?: 'У вас нет прав для выполнения этого действия.',
                ], 403);
            }
        }

        return parent::render($request, $e);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->is('api') || $request->is('api/*')) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Для админки Filament направим на страницу входа админки
        return redirect()->guest('/admin/login');
    }
}
