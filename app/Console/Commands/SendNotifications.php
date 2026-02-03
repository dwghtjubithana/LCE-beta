<?php

namespace App\Console\Commands;

use App\Models\AppNotification;
use App\Services\AuditLogService;
use App\Services\NotificationSender;
use Illuminate\Console\Command;

class SendNotifications extends Command
{
    protected $signature = 'lce:send-notifications';
    protected $description = 'Send pending notifications (placeholder sender).';

    public function handle(NotificationSender $sender): int
    {
        $pending = AppNotification::pending()->get();
        $sentCount = 0;
        foreach ($pending as $notification) {
            if ($sender->send($notification)) {
                $notification->sent_at = now();
                $notification->save();
                $sentCount += 1;
            }
        }

        if ($sentCount > 0) {
            app(AuditLogService::class)->record(null, 'notifications.sent', 'notification', null, [
                'count' => $sentCount,
            ]);
        }

        return self::SUCCESS;
    }
}
