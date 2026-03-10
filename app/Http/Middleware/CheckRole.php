<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthenticated.');
        }

        // role:Admin,HR,Developer
        $allowed = array_map(fn ($r) => trim((string) $r), $roles);

        // Pull role title from employees.role_id -> roles.title
        $roleTitle = optional(optional($user->employee)->role)->title;

        if (!$roleTitle || !in_array($roleTitle, $allowed, true)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}