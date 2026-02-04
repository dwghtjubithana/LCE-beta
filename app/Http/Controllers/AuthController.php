<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, JwtService $jwt, AuditLogService $audit): JsonResponse
    {
        $email = $this->normalizeEmail($request->input('email'));
        $phone = $this->normalizePhone($request->input('phone'));
        $username = $request->input('username') ?: $this->deriveUsername($email, $phone);

        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'username' => $username,
            'email' => $email,
            'phone' => $phone,
            'password_hash' => Hash::make($request->input('password')),
            'app_role' => 'user',
            'status' => 'ACTIVE',
        ]);

        $token = $jwt->createToken([
            'sub' => $user->id,
            'uid' => $user->uuid,
        ], $this->jwtTtl());

        $audit->record($user, 'auth.register', 'user', $user->id, [
            'email' => $user->email,
        ]);

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'expires_in' => $this->jwtTtl() * 60,
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function login(LoginRequest $request, JwtService $jwt, AuditLogService $audit): JsonResponse
    {
        $email = $this->normalizeEmail($request->input('email'));
        $phone = $this->normalizePhone($request->input('phone'));

        $query = User::query();
        if ($email) {
            $query->where('email', $email);
        } elseif ($phone) {
            $query->where('phone', $phone);
        }
        $user = $query->first();

        if (!$user || !Hash::check($request->input('password'), $user->password_hash)) {
            return response()->json([
                'code' => 'INVALID_CREDENTIALS',
                'message' => 'Invalid login credentials.',
            ], 401);
        }

        $token = $jwt->createToken([
            'sub' => $user->id,
            'uid' => $user->uuid,
        ], $this->jwtTtl());

        $audit->record($user, 'auth.login', 'user', $user->id, [
            'email' => $user->email,
        ]);

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'expires_in' => $this->jwtTtl() * 60,
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = request()->attributes->get('auth_user');

        return response()->json([
            'status' => 'success',
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(AuditLogService $audit): JsonResponse
    {
        $user = request()->attributes->get('auth_user');
        if ($user) {
            $audit->record($user, 'auth.logout', 'user', $user->id, [
                'email' => $user->email,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out. Please discard the token on the client.',
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'email' => $user->email,
            'phone' => $user->phone,
            'username' => $user->username,
            'role' => $user->app_role,
            'plan' => $user->plan,
            'plan_status' => $user->plan_status,
            'status' => $user->status,
        ];
    }

    private function normalizeEmail(?string $email): ?string
    {
        $email = trim((string) $email);
        return $email === '' ? null : strtolower($email);
    }

    private function normalizePhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return null;
        }
        $normalized = preg_replace('/[^0-9+]/', '', $phone);
        return $normalized ?: null;
    }

    private function deriveUsername(?string $email, ?string $phone): string
    {
        if ($email) {
            $prefix = explode('@', $email)[0];
            return substr($prefix, 0, 100);
        }
        if ($phone) {
            return substr('user_' . ltrim($phone, '+'), 0, 100);
        }
        return 'user_' . Str::random(8);
    }

    private function jwtTtl(): int
    {
        return (int) (env('JWT_TTL', 60));
    }
}
