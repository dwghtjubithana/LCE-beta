<?php

namespace App\Http\Controllers;

use App\Models\EmailLog;
use App\Models\EmailTemplate;
use App\Services\AuditLogService;
use App\Services\EmailSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminEmailSettingsController extends Controller
{
    public function paymentSettings(EmailSettingsService $emailSettings): JsonResponse
    {
        $settings = $emailSettings->settings();
        return response()->json([
            'status' => 'success',
            'settings' => [
                'payment_bank_name' => $settings['payment_bank_name'] ?? 'TCB',
                'payment_bank_account' => $settings['payment_bank_account'] ?? '',
                'payment_bank_account_name' => $settings['payment_bank_account_name'] ?? '',
            ],
        ]);
    }

    public function show(EmailSettingsService $emailSettings): JsonResponse
    {
        $settings = $emailSettings->settings();
        unset($settings['email_smtp_password']);
        return response()->json([
            'status' => 'success',
            'settings' => $settings,
            'templates' => $emailSettings->templates(),
            'default_template_keys' => EmailSettingsService::DEFAULT_TEMPLATE_KEYS,
        ]);
    }

    public function update(Request $request, EmailSettingsService $emailSettings, AuditLogService $audit): JsonResponse
    {
        $payload = $request->validate([
            'email_enabled' => ['nullable', 'boolean'],
            'email_mailer' => ['nullable', 'in:smtp,sendmail,log,array'],
            'email_smtp_host' => ['nullable', 'string', 'max:255'],
            'email_smtp_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'email_smtp_encryption' => ['nullable', 'in:tls,ssl'],
            'email_smtp_username' => ['nullable', 'string', 'max:255'],
            'email_smtp_password' => ['nullable', 'string', 'max:255'],
            'email_from_name' => ['nullable', 'string', 'max:255'],
            'email_from_address' => ['nullable', 'email', 'max:255'],
            'email_reply_to_name' => ['nullable', 'string', 'max:255'],
            'email_reply_to_address' => ['nullable', 'email', 'max:255'],
            'email_verification_link_base_url' => ['nullable', 'string', 'max:500'],
            'email_verification_token_ttl_minutes' => ['nullable', 'integer', 'min:5', 'max:10080'],
            'email_send_welcome' => ['nullable', 'boolean'],
            'email_send_verification' => ['nullable', 'boolean'],
            'email_send_notifications' => ['nullable', 'boolean'],
            'payment_bank_name' => ['nullable', 'string', 'max:255'],
            'payment_bank_account' => ['nullable', 'string', 'max:255'],
            'payment_bank_account_name' => ['nullable', 'string', 'max:255'],
            'templates' => ['nullable', 'array'],
            'templates.*.template_key' => ['required_with:templates', 'string', 'max:120'],
            'templates.*.name' => ['required_with:templates', 'string', 'max:255'],
            'templates.*.subject' => ['required_with:templates', 'string', 'max:255'],
            'templates.*.body' => ['required_with:templates', 'string'],
            'templates.*.is_active' => ['nullable', 'boolean'],
        ]);

        $templates = $payload['templates'] ?? [];
        unset($payload['templates']);
        $emailSettings->updateSettings($payload);

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['template_key' => $template['template_key']],
                [
                    'name' => $template['name'],
                    'subject' => $template['subject'],
                    'body' => $template['body'],
                    'is_active' => array_key_exists('is_active', $template) ? (bool) $template['is_active'] : true,
                ]
            );
        }

        $audit->record($this->authUser(), 'admin.email_settings.update', 'email_settings', null, [
            'settings_keys' => array_keys($payload),
            'templates_updated' => count($templates),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Email settings updated.',
        ]);
    }

    public function sendTest(Request $request, EmailSettingsService $emailSettings, AuditLogService $audit): JsonResponse
    {
        $payload = $request->validate([
            'to_email' => ['required', 'email', 'max:255'],
            'template_key' => ['nullable', 'string', 'max:120'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
        ]);

        $settings = $emailSettings->settings();
        if (!$settings['email_enabled']) {
            return response()->json([
                'code' => 'EMAIL_DISABLED',
                'message' => 'Email sending is disabled in admin settings.',
            ], 422);
        }

        $templateKey = $payload['template_key'] ?: 'test_email';
        $rendered = $emailSettings->renderTemplate($templateKey, [
            'name' => 'Test gebruiker',
        ]);

        $subject = $payload['subject'] ?? ($rendered['subject'] ?? 'Test e-mail vanaf SuriCore LCE');
        $body = $payload['body'] ?? ($rendered['body'] ?? 'Dit is een testmail.');

        try {
            $emailSettings->applyRuntimeMailConfig();
            Mail::raw($body, function ($message) use ($payload, $subject, $settings) {
                $message->to($payload['to_email'])->subject($subject);
                if (!empty($settings['email_reply_to_address'])) {
                    $message->replyTo(
                        $settings['email_reply_to_address'],
                        $settings['email_reply_to_name'] ?: null
                    );
                }
            });

            EmailLog::create([
                'template_key' => $templateKey,
                'to_email' => $payload['to_email'],
                'subject' => $subject,
                'status' => 'SENT',
                'meta' => ['source' => 'admin_test'],
            ]);

            $audit->record($this->authUser(), 'admin.email_settings.test_send', 'email', null, [
                'to_email' => $payload['to_email'],
                'template_key' => $templateKey,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Test email sent.',
            ]);
        } catch (\Throwable $e) {
            EmailLog::create([
                'template_key' => $templateKey,
                'to_email' => $payload['to_email'],
                'subject' => $subject,
                'status' => 'FAILED',
                'error_message' => $e->getMessage(),
                'meta' => ['source' => 'admin_test'],
            ]);

            return response()->json([
                'code' => 'EMAIL_SEND_FAILED',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function logs(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 ? min($limit, 100) : 20;
        $page = (int) $request->query('page', 1);
        $page = $page > 0 ? $page : 1;

        $query = EmailLog::query()->orderByDesc('id');
        $total = (clone $query)->count();
        $logs = $query->forPage($page, $limit)->get();

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
