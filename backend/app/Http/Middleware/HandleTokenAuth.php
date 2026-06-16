<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class HandleTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->has('token') && !$request->bearerToken()) {
            $token = $request->query('token');
            $request->headers->set('Authorization', 'Bearer ' . $token);
        }

        return $next($request);
    }
}

