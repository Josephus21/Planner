<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, ...$permissionKeys): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401);
        }

        // ✅ Developer bypass (or Admin bypass)
        $role = $user->role ?? null; // adjust if different
        if (in_array($role, ['Developer', 'Admin'], true)) {
            return $next($request);
        }

        // If no permission keys provided, allow
        if (empty($permissionKeys)) {
            return $next($request);
        }

        // ✅ If no permission method exists, block
        if (!method_exists($user, 'hasPermission')) {
            abort(403, 'Unauthorized action.');
        }

        // ✅ OR logic: allow if user has at least 1 of the required permissions
        foreach ($permissionKeys as $perm) {
            if ($user->hasPermission($perm)) {
                return $next($request);
            }
        }

        abort(403, 'Unauthorized action.');
    }
}