<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\EmailLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationSender
{
    public function __construct(private readonly EmailSettingsService $emailSettings)
    {
    }

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

        $settings = $this->emailSettings->settings();
        if (!$settings['email_enabled'] || !$settings['email_send_notifications']) {
            $this->logDelivery([
                'template_key' => 'expiring_soon',
                'to_email' => (string) ($notification->user?->email ?? 'unknown'),
                'subject' => 'Notification skipped',
                'status' => 'SKIPPED',
                'error_message' => 'Email sending disabled by admin settings.',
                'meta' => $payload,
            ]);
            return true;
        }

        $toEmail = (string) ($notification->user?->email ?? '');
        if ($toEmail === '') {
            $this->logDelivery([
                'template_key' => 'expiring_soon',
                'to_email' => 'unknown',
                'subject' => 'Notification skipped',
                'status' => 'SKIPPED',
                'error_message' => 'Target user has no email address.',
                'meta' => $payload,
            ]);
            return true;
        }

        try {
            $template = $this->emailSettings->renderTemplate('expiring_soon', [
                'name' => $notification->user?->username ?: 'gebruiker',
                'document_type' => $notification->type,
            ]);
            $subject = $template['subject'] ?? 'Document update';
            $body = $template['body'] ?? 'Er is een update voor je documenten.';
            $this->emailSettings->applyRuntimeMailConfig();
            Mail::raw($body, function ($message) use ($toEmail, $subject, $settings) {
                $message->to($toEmail)->subject($subject);
                if (!empty($settings['email_reply_to_address'])) {
                    $message->replyTo(
                        $settings['email_reply_to_address'],
                        $settings['email_reply_to_name'] ?: null
                    );
                }
            });

            $this->logDelivery([
                'template_key' => 'expiring_soon',
                'to_email' => $toEmail,
                'subject' => Str::limit($subject, 255, ''),
                'status' => 'SENT',
                'meta' => $payload,
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('Email notification send failed.', array_merge($payload, [
                'error' => $e->getMessage(),
            ]));
            $this->logDelivery([
                'template_key' => 'expiring_soon',
                'to_email' => $toEmail,
                'subject' => isset($subject) ? Str::limit($subject, 255, '') : 'Document update',
                'status' => 'FAILED',
                'error_message' => $e->getMessage(),
                'meta' => $payload,
            ]);
            return false;
        }
    }

    private function logDelivery(array $data): void
    {
        try {
            EmailLog::create($data);
        } catch (\Throwable $e) {
            Log::warning('Email log write skipped.', [
                'error' => $e->getMessage(),
                'template_key' => $data['template_key'] ?? null,
            ]);
        }
    }
}
