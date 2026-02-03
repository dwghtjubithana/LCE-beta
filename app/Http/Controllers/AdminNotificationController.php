<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Services\AuditLogService;
use App\Services\NotificationSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function index(Request $request, AuditLogService $audit): JsonResponse
    {
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 ? min($limit, 100) : 20;
        $page = (int) $request->query('page', 1);
        $page = $page > 0 ? $page : 1;
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $query = AppNotification::query()->orderByDesc('id');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('type', 'like', "%{$search}%")
                    ->orWhere('channel', 'like', "%{$search}%")
                    ->orWhere('user_id', $search)
                    ->orWhere('company_id', $search)
                    ->orWhere('document_id', $search);
            });
        }
        if ($status === 'pending') {
            $query->whereNull('sent_at');
        }
        if ($status === 'sent') {
            $query->whereNotNull('sent_at');
        }

        $total = (clone $query)->count();
        $notifications = $query->forPage($page, $limit)->get();

        $audit->record($this->authUser(), 'admin.notifications.view', 'notification', null, [
            'limit' => $limit,
            'page' => $page,
            'search' => $search ?: null,
            'status' => $status ?: null,
        ]);

        return response()->json([
            'status' => 'success',
            'notifications' => $notifications,
            'meta' => [
                'page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => $limit > 0 ? (int) ceil($total / $limit) : 1,
            ],
        ]);
    }

    public function markBulk(Request $request, AuditLogService $audit): JsonResponse
    {
        $ids = $request->input('ids');
        $status = $request->input('status');
        $query = AppNotification::query();

        if (is_array($ids) && $ids) {
            $query->whereIn('id', $ids);
        } elseif ($status === 'pending') {
            $query->whereNull('sent_at');
        } else {
            return response()->json([
                'code' => 'VALIDATION_ERROR',
                'message' => 'Provide ids or status=pending.',
            ], 422);
        }

        $sentAt = $request->input('sent_at') ?: now();
        $count = $query->update(['sent_at' => $sentAt]);

        $audit->record($this->authUser(), 'notification.mark_bulk', 'notification', null, [
            'count' => $count,
            'status' => $status,
        ]);

        return response()->json([
            'status' => 'success',
            'updated' => $count,
        ]);
    }

    public function resend(NotificationSender $sender, AuditLogService $audit, int $id): JsonResponse
    {
        $notification = AppNotification::find($id);
        if (!$notification) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Notification not found.',
            ], 404);
        }

        $sent = $sender->send($notification);
        if ($sent) {
            $notification->sent_at = now();
            $notification->save();
        }

        $audit->record($this->authUser(), 'notification.resend', 'notification', $notification->id, [
            'channel' => $notification->channel,
        ]);

        return response()->json([
            'status' => 'success',
            'notification' => $notification,
        ]);
    }

    public function markSent(Request $request, AuditLogService $audit, int $id): JsonResponse
    {
        $notification = AppNotification::find($id);
        if (!$notification) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Notification not found.',
            ], 404);
        }

        $notification->sent_at = $request->input('sent_at') ?: now();
        $notification->save();

        $audit->record($this->authUser(), 'notification.mark_sent', 'notification', $notification->id, [
            'channel' => $notification->channel,
        ]);

        return response()->json([
            'status' => 'success',
            'notification' => $notification,
        ]);
    }

    public function show(AuditLogService $audit, int $id): JsonResponse
    {
        $notification = AppNotification::find($id);
        if (!$notification) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Notification not found.',
            ], 404);
        }

        $audit->record($this->authUser(), 'admin.notifications.view_one', 'notification', $notification->id);

        return response()->json([
            'status' => 'success',
            'notification' => $notification,
        ]);
    }

    private function authUser()
    {
        return request()->attributes->get('auth_user');
    }
}
