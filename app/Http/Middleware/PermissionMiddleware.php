<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();
        if (! $user || ! $user->is_active) {
            abort(403);
        }

        if (! $user->hasPermission($permission)) { // metodo che aggiungiamo nel model
            abort(403);
        }

        return $next($request);
    }
}
