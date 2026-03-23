<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Config;

class EmailSettingsService
{
    public const DEFAULT_TEMPLATE_KEYS = [
        'welcome',
        'email_verification',
        'expiring_soon',
        'test_email',
    ];

    public function settings(bool $includeSecrets = false): array
    {
        $smtpPassword = $includeSecrets ? (string) AppSetting::getValue('email_smtp_password', '') : null;
        return [
            'email_enabled' => $this->toBool(AppSetting::getValue('email_enabled', '1')),
            'email_mailer' => (string) AppSetting::getValue('email_mailer', 'smtp'),
            'email_smtp_host' => (string) AppSetting::getValue('email_smtp_host', ''),
            'email_smtp_port' => (int) AppSetting::getValue('email_smtp_port', 587),
            'email_smtp_encryption' => (string) AppSetting::getValue('email_smtp_encryption', 'tls'),
            'email_smtp_username' => (string) AppSetting::getValue('email_smtp_username', ''),
            'email_smtp_password' => $smtpPassword,
            'email_from_name' => (string) AppSetting::getValue('email_from_name', config('app.name', 'SuriCore LCE')),
            'email_from_address' => (string) AppSetting::getValue('email_from_address', 'noreply@example.com'),
            'email_reply_to_name' => (string) AppSetting::getValue('email_reply_to_name', ''),
            'email_reply_to_address' => (string) AppSetting::getValue('email_reply_to_address', ''),
            'email_verification_link_base_url' => (string) AppSetting::getValue('email_verification_link_base_url', ''),
            'email_verification_token_ttl_minutes' => (int) AppSetting::getValue('email_verification_token_ttl_minutes', 1440),
            'email_send_welcome' => $this->toBool(AppSetting::getValue('email_send_welcome', '1')),
            'email_send_verification' => $this->toBool(AppSetting::getValue('email_send_verification', '1')),
            'email_send_notifications' => $this->toBool(AppSetting::getValue('email_send_notifications', '1')),
            'email_smtp_password_set' => AppSetting::hasRawValue('email_smtp_password'),
            'payment_bank_name' => (string) AppSetting::getValue('payment_bank_name', 'TCB'),
            'payment_bank_account' => (string) AppSetting::getValue('payment_bank_account', '12.34.56.789'),
            'payment_bank_account_name' => (string) AppSetting::getValue('payment_bank_account_name', 'Wapcomtek NV'),
        ];
    }

    public function updateSettings(array $payload): void
    {
        foreach ($payload as $key => $value) {
            if ($key === 'email_smtp_password' && ($value === null || $value === '')) {
                continue;
            }
            if ($key === 'email_smtp_password_set') {
                continue;
            }
            AppSetting::setValue($key, $value);
        }
    }

    public function templates()
    {
        return EmailTemplate::orderBy('template_key')->get();
    }

    public function applyRuntimeMailConfig(): void
    {
        $settings = $this->settings(true);

        Config::set('mail.default', $settings['email_mailer'] ?: 'smtp');
        Config::set('mail.mailers.smtp.host', $settings['email_smtp_host'] ?: env('MAIL_HOST', '127.0.0.1'));
        Config::set('mail.mailers.smtp.port', $settings['email_smtp_port'] ?: (int) env('MAIL_PORT', 587));
        Config::set('mail.mailers.smtp.username', $settings['email_smtp_username'] ?: env('MAIL_USERNAME'));
        Config::set('mail.mailers.smtp.password', $settings['email_smtp_password'] ?: env('MAIL_PASSWORD'));
        Config::set('mail.mailers.smtp.encryption', $settings['email_smtp_encryption'] ?: env('MAIL_ENCRYPTION', 'tls'));
        Config::set('mail.from.address', $settings['email_from_address'] ?: env('MAIL_FROM_ADDRESS', 'hello@example.com'));
        Config::set('mail.from.name', $settings['email_from_name'] ?: env('MAIL_FROM_NAME', config('app.name')));
        Config::set('mail.reply_to.address', $settings['email_reply_to_address'] ?: null);
        Config::set('mail.reply_to.name', $settings['email_reply_to_name'] ?: null);
    }

    public function renderTemplate(string $templateKey, array $variables = []): ?array
    {
        $template = EmailTemplate::where('template_key', $templateKey)->first();
        if (!$template || !$template->is_active) {
            return null;
        }

        $subject = $this->injectVariables($template->subject, $variables);
        $body = $this->injectVariables($template->body, $variables);

        return [
            'template_key' => $templateKey,
            'subject' => $subject,
            'body' => $body,
        ];
    }

    private function injectVariables(string $content, array $variables): string
    {
        $merged = array_merge([
            'app_name' => (string) config('app.name', 'SuriCore LCE'),
            'timestamp' => now()->toDateTimeString(),
        ], $variables);

        $result = $content;
        foreach ($merged as $key => $value) {
            $result = str_replace('{{' . $key . '}}', (string) $value, $result);
        }
        return $result;
    }

    private function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        return in_array((string) $value, ['1', 'true', 'TRUE', 'yes', 'on'], true);
    }
}
