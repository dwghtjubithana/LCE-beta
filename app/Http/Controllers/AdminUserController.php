<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index(Request $request, AuditLogService $audit): JsonResponse
    {
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 ? min($limit, 50) : 20;
        $page = (int) $request->query('page', 1);
        $page = $page > 0 ? $page : 1;
        $search = trim((string) $request->query('search', ''));
        $role = trim((string) $request->query('role', ''));
        $status = trim((string) $request->query('status', ''));
        $plan = trim((string) $request->query('plan', ''));

        $query = User::query()->orderByDesc('id');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }
        if ($role !== '') {
            $query->where('app_role', $role);
        }
        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($plan !== '') {
            $query->where('plan', $plan);
        }

        $total = (clone $query)->count();
        $users = $query->forPage($page, $limit)->get();

        $audit->record($this->authUser(), 'admin.users.view', 'user', null, [
            'limit' => $limit,
            'page' => $page,
            'search' => $search ?: null,
            'role' => $role ?: null,
            'status' => $status ?: null,
            'plan' => $plan ?: null,
        ]);

        return response()->json([
            'status' => 'success',
            'users' => $users,
            'meta' => [
                'page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => $limit > 0 ? (int) ceil($total / $limit) : 1,
            ],
        ]);
    }

    public function show(AuditLogService $audit, int $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'User not found.',
            ], 404);
        }

        $audit->record($this->authUser(), 'admin.users.view_one', 'user', $user->id);

        return response()->json([
            'status' => 'success',
            'user' => $user,
        ]);
    }

    public function update(Request $request, AuditLogService $audit, int $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'User not found.',
            ], 404);
        }

        $data = $request->validate([
            'app_role' => ['sometimes', 'in:user,admin'],
            'status' => ['sometimes', 'in:ACTIVE,SUSPENDED'],
            'plan' => ['sometimes', 'in:FREE,PRO,BUSINESS'],
            'plan_status' => ['sometimes', 'in:ACTIVE,PENDING_PAYMENT,EXPIRED'],
        ]);

        if (!$data) {
            return response()->json([
                'code' => 'VALIDATION_ERROR',
                'message' => 'Provide at least one field to update.',
            ], 422);
        }

        $user->fill($data);
        $user->save();

        $audit->record($this->authUser(), 'user.admin_update', 'user', $user->id, [
            'updated_fields' => array_keys($data),
        ]);

        return response()->json([
            'status' => 'success',
            'user' => $user,
        ]);
    }

    public function store(Request $request, AuditLogService $audit): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8'],
            'app_role' => ['sometimes', 'in:user,admin'],
            'status' => ['sometimes', 'in:ACTIVE,SUSPENDED'],
            'plan' => ['sometimes', 'in:FREE,PRO,BUSINESS'],
            'plan_status' => ['sometimes', 'in:ACTIVE,PENDING_PAYMENT,EXPIRED'],
        ]);

        if (empty($data['email']) && empty($data['phone'])) {
            return response()->json([
                'code' => 'VALIDATION_ERROR',
                'message' => 'Provide at least an email or phone number.',
            ], 422);
        }

        if (!empty($data['email']) && User::where('email', $data['email'])->exists()) {
            return response()->json([
                'code' => 'VALIDATION_ERROR',
                'message' => 'Email already exists.',
            ], 422);
        }
        if (!empty($data['phone']) && User::where('phone', $data['phone'])->exists()) {
            return response()->json([
                'code' => 'VALIDATION_ERROR',
                'message' => 'Phone already exists.',
            ], 422);
        }

        $user = new User();
        $user->username = $data['username'] ?? null;
        $user->email = $data['email'] ?? null;
        $user->phone = $data['phone'] ?? null;
        $user->password_hash = Hash::make($data['password']);
        $user->role = 'STAFF';
        $user->app_role = $data['app_role'] ?? 'user';
        $user->status = $data['status'] ?? 'ACTIVE';
        $user->plan = $data['plan'] ?? 'FREE';
        $user->plan_status = $data['plan_status'] ?? 'ACTIVE';
        $user->save();

        $audit->record($this->authUser(), 'admin.users.create', 'user', $user->id, [
            'email' => $user->email,
            'phone' => $user->phone,
        ]);

        return response()->json([
            'status' => 'success',
            'user' => $user,
        ], 201);
    }

    private function authUser()
    {
        return request()->attributes->get('auth_user');
    }
}
