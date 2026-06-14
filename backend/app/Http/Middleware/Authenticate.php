<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Для всех API запросов не перенаправляем, возвращаем null
        // Это предотвращает ошибку "Route [login] not defined" для API
        if ($request->expectsJson() || $request->is('api/*') || str_starts_with($request->path(), 'api/')) {
            return null;
        }
        
        // Для веб-запросов пытаемся найти маршрут login, если его нет - возвращаем null
        try {
            return route('login');
        } catch (\Illuminate\Routing\Exceptions\UrlGenerationException $e) {
            return null;
        }
    }
}
