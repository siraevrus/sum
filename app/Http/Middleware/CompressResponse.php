<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Сжимаем только JSON-ответы, если клиент поддерживает gzip и не в окружении testing
        if (!app()->environment('testing')
            && !$response->headers->has('Content-Encoding')
            && str_contains((string) $response->headers->get('Content-Type'), 'application/json')
            && str_contains((string) $request->header('Accept-Encoding'), 'gzip')) {
            $content = $response->getContent();
            
            // Сжимаем контент
            $compressed = gzencode($content, 9);
            
            if ($compressed !== false) {
                $response->setContent($compressed);
                $response->headers->set('Content-Encoding', 'gzip');
                // Не выставляем Content-Length вручную, чтобы избежать несоответствий при чанкованной передаче
                $response->headers->remove('Content-Length');
                $response->headers->set('Vary', 'Accept-Encoding');
            }
        }

        return $response;
    }
} 