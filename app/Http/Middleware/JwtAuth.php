<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class JwtAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $authHeader = $request->header('Authorization', '');
        if (!str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'code' => 'UNAUTHORIZED',
                'message' => 'Missing bearer token.',
            ], 401);
        }

        $token = trim(substr($authHeader, 7));
        if ($token === '') {
            return response()->json([
                'code' => 'UNAUTHORIZED',
                'message' => 'Missing bearer token.',
            ], 401);
        }

        try {
            $payload = app(JwtService::class)->decode($token);
        } catch (\Throwable $e) {
            return response()->json([
                'code' => 'UNAUTHORIZED',
                'message' => 'Invalid token.',
            ], 401);
        }

        $userId = $payload->sub ?? null;
        if (!$userId) {
            return response()->json([
                'code' => 'UNAUTHORIZED',
                'message' => 'Invalid token payload.',
            ], 401);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json([
                'code' => 'UNAUTHORIZED',
                'message' => 'User not found.',
            ], 401);
        }

        $request->attributes->set('auth_user', $user);
        Log::withContext(['user_id' => $user->id]);

        return $next($request);
    }
}
