<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        // Enforce admin role checks by default in all environments.
        // Optional bypass is allowed for local development only.
        $enforce = filter_var((string) env('ADMIN_ENFORCE_API', 'true'), FILTER_VALIDATE_BOOLEAN);
        if (!$enforce && app()->environment('local')) {
            return $next($request);
        }

        $user = $request->attributes->get('auth_user');
        if (!$user || ($user->app_role ?? 'user') !== 'admin') {
            return response()->json([
                'code' => 'FORBIDDEN',
                'message' => 'Admin access required.',
            ], 403);
        }

        return $next($request);
    }
}
