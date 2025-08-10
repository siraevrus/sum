<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
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
}


