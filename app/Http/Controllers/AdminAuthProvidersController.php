<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminAuthProvidersController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'settings' => [
                'auth_google_enabled' => $this->toBool(AppSetting::getValue('auth_google_enabled', '0')),
                'auth_google_client_id' => (string) AppSetting::getValue('auth_google_client_id', ''),
                'auth_google_client_secret_set' => AppSetting::hasRawValue('auth_google_client_secret'),
                'auth_google_redirect_uri' => (string) AppSetting::getValue('auth_google_redirect_uri', ''),
                'auth_google_prompt' => (string) AppSetting::getValue('auth_google_prompt', 'select_account'),
                'auth_microsoft_enabled' => $this->toBool(AppSetting::getValue('auth_microsoft_enabled', '0')),
                'auth_microsoft_client_id' => (string) AppSetting::getValue('auth_microsoft_client_id', ''),
                'auth_microsoft_client_secret_set' => AppSetting::hasRawValue('auth_microsoft_client_secret'),
                'auth_microsoft_redirect_uri' => (string) AppSetting::getValue('auth_microsoft_redirect_uri', ''),
                'auth_microsoft_tenant' => (string) AppSetting::getValue('auth_microsoft_tenant', 'common'),
            ],
        ]);
    }

    public function update(Request $request, AuditLogService $audit): JsonResponse
    {
        $payload = $request->validate([
            'auth_google_enabled' => ['nullable', 'boolean'],
            'auth_google_client_id' => ['nullable', 'string', 'max:255'],
            'auth_google_client_secret' => ['nullable', 'string', 'max:255'],
            'auth_google_redirect_uri' => ['nullable', 'url', 'max:500'],
            'auth_google_prompt' => ['nullable', 'in:select_account,consent,none'],
            'auth_microsoft_enabled' => ['nullable', 'boolean'],
            'auth_microsoft_client_id' => ['nullable', 'string', 'max:255'],
            'auth_microsoft_client_secret' => ['nullable', 'string', 'max:255'],
            'auth_microsoft_redirect_uri' => ['nullable', 'url', 'max:500'],
            'auth_microsoft_tenant' => ['nullable', 'string', 'max:120'],
        ]);

        $payload['auth_google_redirect_uri'] = $this->validateRedirectUri(
            $payload['auth_google_redirect_uri'] ?? null,
            '/auth/oauth/google/callback',
            'auth_google_redirect_uri'
        );
        $payload['auth_microsoft_redirect_uri'] = $this->validateRedirectUri(
            $payload['auth_microsoft_redirect_uri'] ?? null,
            '/auth/oauth/microsoft/callback',
            'auth_microsoft_redirect_uri'
        );

        foreach ($payload as $key => $value) {
            if (in_array($key, ['auth_google_client_secret', 'auth_microsoft_client_secret'], true) && ($value === null || $value === '')) {
                continue;
            }
            AppSetting::setValue($key, $value);
        }

        $audit->record($this->authUser(), 'admin.auth_providers.update', 'auth_providers', null, [
            'settings_keys' => array_keys($payload),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Auth provider settings updated.',
        ]);
    }

    public function publicConfig(): JsonResponse
    {
        $googleEnabled = $this->toBool(AppSetting::getValue('auth_google_enabled', '0'))
            && (string) AppSetting::getValue('auth_google_client_id', '') !== ''
            && AppSetting::hasRawValue('auth_google_client_secret');
        $microsoftEnabled = $this->toBool(AppSetting::getValue('auth_microsoft_enabled', '0'))
            && (string) AppSetting::getValue('auth_microsoft_client_id', '') !== ''
            && AppSetting::hasRawValue('auth_microsoft_client_secret');

        return response()->json([
            'status' => 'success',
            'providers' => [
                'google' => [
                    'enabled' => $googleEnabled,
                    'start_url' => $googleEnabled ? '/auth/oauth/google/start' : null,
                ],
                'microsoft' => [
                    'enabled' => $microsoftEnabled,
                    'start_url' => $microsoftEnabled ? '/auth/oauth/microsoft/start' : null,
                ],
            ],
        ]);
    }

    private function authUser()
    {
        return request()->attributes->get('auth_user');
    }

    private function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        return in_array((string) $value, ['1', 'true', 'TRUE', 'yes', 'on'], true);
    }

    private function validateRedirectUri(?string $value, string $expectedPath, string $field): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $parts = parse_url($value);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            throw ValidationException::withMessages([
                $field => 'Redirect URI moet een geldige absolute URL zijn.',
            ]);
        }
        if (!in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            throw ValidationException::withMessages([
                $field => 'Redirect URI moet http of https gebruiken.',
            ]);
        }
        if (!empty($parts['query']) || !empty($parts['fragment'])) {
            throw ValidationException::withMessages([
                $field => 'Redirect URI mag geen query parameters of fragment bevatten.',
            ]);
        }
        if (($parts['path'] ?? '') !== $expectedPath) {
            throw ValidationException::withMessages([
                $field => 'Redirect URI moet eindigen op ' . $expectedPath,
            ]);
        }

        return $value;
    }
}
