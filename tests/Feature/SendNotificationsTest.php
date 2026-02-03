<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\User;
use App\Services\NotificationSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class SendNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_notifications_marks_as_sent(): void
    {
        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'username' => 'notify',
            'email' => 'notify@example.com',
            'password_hash' => Hash::make('password123'),
            'app_role' => 'user',
            'status' => 'ACTIVE',
            'plan' => 'PRO',
            'plan_status' => 'ACTIVE',
        ]);

        $first = AppNotification::create([
            'user_id' => $user->id,
            'type' => 'EXPIRING_SOON',
            'channel' => 'email',
        ]);

        $second = AppNotification::create([
            'user_id' => $user->id,
            'type' => 'EXPIRING_SOON',
            'channel' => 'push',
        ]);

        $fakeSender = new class extends NotificationSender {
            public array $sent = [];
            public function send(AppNotification $notification): bool
            {
                $this->sent[] = $notification->id;
                return true;
            }
        };

        $this->app->instance(NotificationSender::class, $fakeSender);

        Artisan::call('lce:send-notifications');

        $this->assertNotNull($first->fresh()->sent_at);
        $this->assertNotNull($second->fresh()->sent_at);
        $this->assertCount(2, $fakeSender->sent);
    }
}
