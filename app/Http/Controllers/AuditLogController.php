<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request, \App\Services\AuditLogService $audit): JsonResponse
    {
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 ? min($limit, 50) : 20;
        $page = (int) $request->query('page', 1);
        $page = $page > 0 ? $page : 1;
        $search = trim((string) $request->query('search', ''));

        $query = AuditLog::query()->orderByDesc('id');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('target_type', 'like', "%{$search}%")
                    ->orWhere('target_id', $search);
            });
        }

        $total = (clone $query)->count();
        $logs = $query->forPage($page, $limit)->get();

        $audit->record($this->authUser(), 'admin.audit_logs.view', 'audit_log', null, [
            'limit' => $limit,
            'page' => $page,
            'search' => $search ?: null,
        ]);

        return response()->json([
            'status' => 'success',
            'logs' => $logs,
            'meta' => [
                'page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => $limit > 0 ? (int) ceil($total / $limit) : 1,
            ],
        ]);
    }

    private function authUser()
    {
        return request()->attributes->get('auth_user');
    }
}
