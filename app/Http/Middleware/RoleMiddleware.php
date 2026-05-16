<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        if (!$user || !$user->is_active) {
            abort(403);
        }

        if (empty($roles)) {
            return $next($request);
        }

        // ruoli ammessi definiti sulla rotta, es: role:admin,superadmin
        $allowed = array_map('strtolower', $roles);
        $current = strtolower($user->role->value);

        if (! in_array($current, $allowed, true)) {
            abort(403);
        }

        return $next($request);
    }
}
