<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;

class AdminHealthController extends Controller
{
    public function show(AuditLogService $audit): JsonResponse
    {
        $lastTender = AuditLog::where('action', 'tenders.import')->latest()->first();
        $lastNotifications = AuditLog::where('action', 'notifications.sent')->latest()->first();

        $audit->record($this->authUser(), 'admin.health.view', 'health', null);

        return response()->json([
            'status' => 'success',
            'health' => [
                'app_env' => config('app.env'),
                'app_version' => app()->version(),
                'queue_connection' => config('queue.default'),
                'last_tender_import_at' => $lastTender?->created_at,
                'last_notifications_sent_at' => $lastNotifications?->created_at,
            ],
        ]);
    }

    private function authUser()
    {
        return request()->attributes->get('auth_user');
    }
}
