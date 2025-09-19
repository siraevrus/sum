<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Основные заголовки безопасности для всего приложения
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // Удаляем информацию о сервере
        $response->headers->remove('Server');
        $response->headers->remove('X-Powered-By');

        // HSTS для HTTPS (только в продакшене)
        if ($request->isSecure() && app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // CSP заголовки в зависимости от типа запроса
        if ($request->is('api/*')) {
            // Строгий CSP для API
            $csp = "default-src 'none'; ".
                   "connect-src 'self'; ".
                   "frame-ancestors 'none'; ".
                   "base-uri 'none';";
        } elseif ($request->is('admin*')) {
            // CSP для админ-панели (уже обрабатывается FilamentSecurityHeaders)
            return $response;
        } else {
            // CSP для основного приложения
            $csp = "default-src 'self'; ".
                   "script-src 'self' 'unsafe-inline'; ".
                   "style-src 'self' 'unsafe-inline'; ".
                   "img-src 'self' data: blob: https:; ".
                   "font-src 'self' data:; ".
                   "connect-src 'self'; ".
                   "media-src 'self'; ".
                   "object-src 'none'; ".
                   "child-src 'none'; ".
                   "worker-src 'self'; ".
                   "frame-ancestors 'none'; ".
                   "form-action 'self'; ".
                   "base-uri 'self'; ".
                   'upgrade-insecure-requests;';
        }

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
