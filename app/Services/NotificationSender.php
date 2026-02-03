<?php

namespace App\Services;

use App\Models\AppNotification;
use Illuminate\Support\Facades\Log;

class NotificationSender
{
    public function send(AppNotification $notification): bool
    {
        $channel = $notification->channel ?? 'email';
        $payload = [
            'notification_id' => $notification->id,
            'type' => $notification->type,
            'channel' => $channel,
            'user_id' => $notification->user_id,
            'company_id' => $notification->company_id,
            'document_id' => $notification->document_id,
        ];

        if ($channel === 'push') {
            Log::info('Push notification stub sent.', $payload);
            return true;
        }

        Log::info('Email notification stub sent.', $payload);
        return true;
    }
}
