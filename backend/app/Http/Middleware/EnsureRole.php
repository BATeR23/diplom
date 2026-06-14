<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    /**
     * @param  array<int, string>  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Не авторизован.'], 401);
        }

        $allowedRoles = array_values(array_filter(array_map('trim', $roles)));

        if (empty($allowedRoles)) {
            return response()->json(['message' => 'Доступ запрещен.'], 403);
        }

        if (!in_array($user->role, $allowedRoles, true)) {
            return response()->json(['message' => 'Доступ запрещен.'], 403);
        }

        return $next($request);
    }
}

