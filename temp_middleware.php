    ->withMiddleware(function (Middleware $middleware): void {
        // Добавляем заголовки безопасности ко всем маршрутам
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
