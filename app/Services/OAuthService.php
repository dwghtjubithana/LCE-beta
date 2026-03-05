<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OAuthService
{
    public function providerConfig(string $provider, bool $requireSecret = false): array
    {
        if ($provider === 'google') {
            $clientId = (string) AppSetting::getValue('auth_google_client_id', '');
            $clientSecret = (string) AppSetting::getValue('auth_google_client_secret', '');
            $enabled = $this->toBool(AppSetting::getValue('auth_google_enabled', '0'));
            $redirectUri = trim((string) AppSetting::getValue('auth_google_redirect_uri', ''));
            if ($redirectUri === '') {
                $redirectUri = rtrim((string) config('app.url'), '/') . '/auth/oauth/google/callback';
            }

            return [
                'enabled' => $enabled && $clientId !== '' && (!$requireSecret || $clientSecret !== ''),
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'prompt' => (string) AppSetting::getValue('auth_google_prompt', 'select_account'),
            ];
        }

        $clientId = (string) AppSetting::getValue('auth_microsoft_client_id', '');
        $clientSecret = (string) AppSetting::getValue('auth_microsoft_client_secret', '');
        $enabled = $this->toBool(AppSetting::getValue('auth_microsoft_enabled', '0'));
        $redirectUri = trim((string) AppSetting::getValue('auth_microsoft_redirect_uri', ''));
        if ($redirectUri === '') {
            $redirectUri = rtrim((string) config('app.url'), '/') . '/auth/oauth/microsoft/callback';
        }
        $tenant = trim((string) AppSetting::getValue('auth_microsoft_tenant', 'common')) ?: 'common';

        return [
            'enabled' => $enabled && $clientId !== '' && (!$requireSecret || $clientSecret !== ''),
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'tenant' => $tenant,
        ];
    }

    public function buildAuthorizeUrl(string $provider, array $config, string $state): string
    {
        if ($provider === 'google') {
            return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
                'client_id' => $config['client_id'],
                'redirect_uri' => $config['redirect_uri'],
                'response_type' => 'code',
                'scope' => 'openid email profile',
                'state' => $state,
                'access_type' => 'offline',
                'prompt' => $config['prompt'] ?: 'select_account',
            ]);
        }

        return sprintf(
            'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize?%s',
            rawurlencode($config['tenant']),
            http_build_query([
                'client_id' => $config['client_id'],
                'redirect_uri' => $config['redirect_uri'],
                'response_type' => 'code',
                'response_mode' => 'query',
                'scope' => 'openid profile email User.Read',
                'state' => $state,
            ])
        );
    }

    public function exchangeCode(string $provider, string $code, array $config): array
    {
        if ($provider === 'google') {
            $res = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'redirect_uri' => $config['redirect_uri'],
                'grant_type' => 'authorization_code',
            ]);
            if (!$res->ok()) {
                throw new \RuntimeException('Google token exchange failed.');
            }
            return (array) $res->json();
        }

        $res = Http::asForm()->post(
            sprintf('https://login.microsoftonline.com/%s/oauth2/v2.0/token', rawurlencode($config['tenant'])),
            [
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'code' => $code,
                'redirect_uri' => $config['redirect_uri'],
                'grant_type' => 'authorization_code',
            ]
        );
        if (!$res->ok()) {
            throw new \RuntimeException('Microsoft token exchange failed.');
        }
        return (array) $res->json();
    }

    public function fetchProfile(string $provider, array $tokenData, array $config): array
    {
        if ($provider === 'google') {
            return $this->fetchGoogleProfile($tokenData, $config);
        }

        return $this->fetchMicrosoftProfile($tokenData, $config);
    }

    public function upsertUser(string $provider, array $profile): User
    {
        $subject = trim((string) ($profile['subject'] ?? ''));
        $email = $this->normalizeEmail($profile['email'] ?? null);
        if ($subject === '' || !$email) {
            throw new \RuntimeException('OAuth profiel onvolledig.');
        }

        $user = User::where('oauth_provider', $provider)->where('oauth_subject', $subject)->first();
        if (!$user) {
            $user = User::where('email', $email)->first();
        }

        if (!$user) {
            $username = $this->ensureUniqueUsername($this->sanitizeUsername((string) ($profile['name'] ?? $email)));
            return User::create([
                'uuid' => (string) Str::uuid(),
                'username' => $username,
                'email' => $email,
                'oauth_provider' => $provider,
                'oauth_subject' => $subject,
                'password_hash' => Hash::make(Str::random(40)),
                'app_role' => 'user',
                'status' => 'ACTIVE',
                'email_verified_at' => now(),
            ]);
        }

        $user->oauth_provider = $provider;
        $user->oauth_subject = $subject;
        if (!$user->email) {
            $user->email = $email;
        }
        if (!$user->email_verified_at) {
            $user->email_verified_at = now();
        }
        $user->save();

        return $user;
    }

    private function fetchGoogleProfile(array $tokenData, array $config): array
    {
        $idToken = (string) ($tokenData['id_token'] ?? '');
        if ($idToken === '') {
            throw new \RuntimeException('Google id_token ontbreekt.');
        }
        $res = Http::get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $idToken]);
        if (!$res->ok()) {
            throw new \RuntimeException('Google token validatie mislukt.');
        }
        $profile = (array) $res->json();
        if (($profile['aud'] ?? '') !== $config['client_id']) {
            throw new \RuntimeException('Google audience mismatch.');
        }
        if (($profile['email_verified'] ?? 'false') !== 'true') {
            throw new \RuntimeException('Google email is niet geverifieerd.');
        }

        return [
            'subject' => (string) ($profile['sub'] ?? ''),
            'email' => $this->normalizeEmail($profile['email'] ?? null),
            'name' => (string) ($profile['name'] ?? $profile['email'] ?? ''),
        ];
    }

    private function fetchMicrosoftProfile(array $tokenData, array $config): array
    {
        $idToken = (string) ($tokenData['id_token'] ?? '');
        if ($idToken === '') {
            throw new \RuntimeException('Microsoft id_token ontbreekt.');
        }

        $claims = $this->decodeJwtPayload($idToken);
        if (($claims['aud'] ?? '') !== ($config['client_id'] ?? '')) {
            throw new \RuntimeException('Microsoft audience mismatch.');
        }

        $accessToken = (string) ($tokenData['access_token'] ?? '');
        if ($accessToken === '') {
            throw new \RuntimeException('Microsoft access token ontbreekt.');
        }

        $res = Http::withToken($accessToken)->get('https://graph.microsoft.com/oidc/userinfo');
        if (!$res->ok()) {
            throw new \RuntimeException('Microsoft userinfo ophalen mislukt.');
        }
        $profile = (array) $res->json();

        $emailFromClaims = $this->normalizeEmail($claims['email'] ?? $claims['preferred_username'] ?? null);
        $emailFromUserInfo = $this->normalizeEmail($profile['email'] ?? $profile['preferred_username'] ?? null);
        $email = $emailFromUserInfo ?: $emailFromClaims;
        if (!$email) {
            throw new \RuntimeException('Microsoft account heeft geen geldig e-mailadres.');
        }
        if ($emailFromClaims && $emailFromUserInfo && $emailFromClaims !== $emailFromUserInfo) {
            throw new \RuntimeException('Microsoft accountgegevens komen niet overeen.');
        }

        // Guard before account linking: fail hard when explicit verification signals are false.
        $hasVerificationSignal = array_key_exists('email_verified', $claims) || array_key_exists('xms_edov', $claims);
        if ($hasVerificationSignal) {
            $isVerified = false;
            if (array_key_exists('email_verified', $claims)) {
                $isVerified = $claims['email_verified'] === true || $claims['email_verified'] === 'true' || $claims['email_verified'] === 1 || $claims['email_verified'] === '1';
            }
            if (!$isVerified && array_key_exists('xms_edov', $claims)) {
                $isVerified = $claims['xms_edov'] === true || $claims['xms_edov'] === 'true' || $claims['xms_edov'] === 1 || $claims['xms_edov'] === '1';
            }
            if (!$isVerified) {
                throw new \RuntimeException('Microsoft e-mailadres is niet geverifieerd.');
            }
        }

        $subject = (string) ($claims['sub'] ?? $profile['sub'] ?? '');
        if ($subject === '') {
            throw new \RuntimeException('Microsoft subject ontbreekt.');
        }

        return [
            'subject' => $subject,
            'email' => $email,
            'name' => (string) ($profile['name'] ?? $claims['name'] ?? $email),
        ];
    }

    private function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) < 2) {
            throw new \RuntimeException('Ongeldig OAuth token formaat.');
        }

        $payload = $parts[1];
        $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
        $decoded = base64_decode(strtr($payload, '-_', '+/'), true);
        if ($decoded === false) {
            throw new \RuntimeException('OAuth token payload kan niet worden gelezen.');
        }

        $claims = json_decode($decoded, true);
        if (!is_array($claims)) {
            throw new \RuntimeException('OAuth token payload is ongeldig.');
        }

        return $claims;
    }

    private function normalizeEmail(?string $email): ?string
    {
        $email = trim((string) $email);
        return $email === '' ? null : strtolower($email);
    }

    private function sanitizeUsername(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9._-]+/', '-', $value) ?: '';
        $value = trim($value, '-._');
        return substr($value, 0, 100);
    }

    private function ensureUniqueUsername(string $base): string
    {
        $base = $this->sanitizeUsername($base);
        if ($base === '') {
            $base = 'user';
        }
        $candidate = substr($base, 0, 100);
        $suffix = 1;
        while (User::where('username', $candidate)->exists()) {
            $tail = '-' . $suffix;
            $candidate = substr($base, 0, max(1, 100 - strlen($tail))) . $tail;
            $suffix++;
        }
        return $candidate;
    }

    private function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        return in_array((string) $value, ['1', 'true', 'TRUE', 'yes', 'on'], true);
    }
}
