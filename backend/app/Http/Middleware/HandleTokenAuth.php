<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class HandleTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Если токен передан как query параметр, добавляем его в заголовок
        if ($request->has('token') && !$request->bearerToken()) {
            $token = $request->query('token');
            $request->headers->set('Authorization', 'Bearer ' . $token);
            // Также устанавливаем Accept заголовок для корректной обработки API запросов
            if (!$request->expectsJson()) {
                $request->headers->set('Accept', 'application/json');
            }
        }

        return $next($request);
    }
}

