<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        foreach ($roles as $role) {
            // role:admin → must have role=admin
            if ($role === 'admin' && $user->role === 'admin') {
                return $next($request);
            }

            // role:adviser → role=adviser OR (role=admin with is_adviser=true)
            if ($role === 'adviser' && $user->canAdvise()) {
                return $next($request);
            }

            // role:student → direct match
            if ($role === 'student' && $user->role === 'student') {
                return $next($request);
            }
        }

        abort(403, 'Unauthorized.');
    }
}
