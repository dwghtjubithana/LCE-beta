<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogService
{
    public function record(?User $user, string $action, ?string $targetType = null, ?int $targetId = null, array $meta = []): void
    {
        $request = request();
        $payload = array_merge([
            'ip' => $request ? $request->ip() : null,
            'user_agent' => $request ? $request->userAgent() : null,
        ], $meta);

        AuditLog::create([
            'actor_user_id' => $user?->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'meta' => $payload ?: null,
        ]);
    }
}
