<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $envFlag = env('ADMIN_ENFORCE_API');
        if ($envFlag === null) {
            $enforce = config('app.env') === 'production';
        } else {
            $enforce = filter_var($envFlag, FILTER_VALIDATE_BOOLEAN);
        }

        if (!$enforce) {
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
