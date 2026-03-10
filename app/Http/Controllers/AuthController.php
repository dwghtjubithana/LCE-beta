<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\EmailLog;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\EmailSettingsService;
use App\Services\JwtService;
use App\Services\OAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, JwtService $jwt, AuditLogService $audit, EmailSettingsService $emailSettings): JsonResponse
    {
        $email = $this->normalizeEmail($request->input('email'));
        $phone = $this->normalizePhone($request->input('phone'));
        $username = $request->input('username') ?: $this->deriveUsername($email, $phone);
        $verificationRequired = $this->verificationRequired($emailSettings, $email);

        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'username' => $this->ensureUniqueUsername($username),
            'email' => $email,
            'phone' => $phone,
            'password_hash' => Hash::make($request->input('password')),
            'app_role' => 'user',
            'status' => 'ACTIVE',
            'email_verified_at' => $verificationRequired ? null : now(),
        ]);

        if ($verificationRequired) {
            $token = $this->issueEmailVerificationToken($user, $emailSettings);
            $this->sendVerificationEmail($user, $token, $emailSettings);
        }

        $response = [
            'status' => 'success',
            'expires_in' => $this->jwtTtl() * 60,
            'user' => $this->userPayload($user),
            'verification_required' => $verificationRequired,
        ];
        if ($verificationRequired) {
            $response['message'] = 'Registratie gelukt. Controleer je e-mail om je account te activeren.';
        } else {
            $token = $jwt->createToken([
                'sub' => $user->id,
                'uid' => $user->uuid,
            ], $this->jwtTtl());
            $response['token'] = $token;
        }

        $audit->record($user, 'auth.register', 'user', $user->id, [
            'email' => $user->email,
            'verification_required' => $verificationRequired,
        ]);

        return response()->json($response, 201);
    }

    public function login(LoginRequest $request, JwtService $jwt, AuditLogService $audit, EmailSettingsService $emailSettings): JsonResponse
    {
        $email = $this->normalizeEmail($request->input('email'));
        $phone = $this->normalizePhone($request->input('phone'));

        $query = User::query();
        if ($email) {
            $query->where('email', $email);
        } elseif ($phone) {
            $query->where('phone', $phone);
        }
        $user = $query->first();

        if (!$user || !Hash::check($request->input('password'), $user->password_hash)) {
            return response()->json([
                'code' => 'INVALID_CREDENTIALS',
                'message' => 'Invalid login credentials.',
            ], 401);
        }

        if ($this->verificationRequired($emailSettings, $user->email) && !$user->email_verified_at) {
            return response()->json([
                'code' => 'EMAIL_NOT_VERIFIED',
                'message' => 'Please verify your email address before logging in.',
                'verification_required' => true,
            ], 403);
        }

        $token = $jwt->createToken([
            'sub' => $user->id,
            'uid' => $user->uuid,
        ], $this->jwtTtl());

        $audit->record($user, 'auth.login', 'user', $user->id, [
            'email' => $user->email,
        ]);

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'expires_in' => $this->jwtTtl() * 60,
            'user' => $this->userPayload($user),
        ]);
    }

    public function verifyEmail(Request $request, AuditLogService $audit): JsonResponse
    {
        $payload = $request->validate([
            'uid' => ['required', 'string', 'max:36'],
            'token' => ['required', 'string', 'min:16', 'max:255'],
        ]);

        $user = User::where('uuid', $payload['uid'])->first();
        if (!$user || !$user->email_verification_token) {
            return response()->json([
                'code' => 'INVALID_VERIFICATION_TOKEN',
                'message' => 'Verification link is invalid.',
            ], 422);
        }

        if ($user->email_verification_expires_at && now()->greaterThan($user->email_verification_expires_at)) {
            return response()->json([
                'code' => 'VERIFICATION_TOKEN_EXPIRED',
                'message' => 'Verification link has expired.',
            ], 422);
        }

        $tokenHash = hash('sha256', (string) $payload['token']);
        if (!hash_equals((string) $user->email_verification_token, $tokenHash)) {
            return response()->json([
                'code' => 'INVALID_VERIFICATION_TOKEN',
                'message' => 'Verification link is invalid.',
            ], 422);
        }

        $user->email_verified_at = now();
        $user->email_verification_token = null;
        $user->email_verification_expires_at = null;
        $user->save();

        $audit->record($user, 'auth.email_verified', 'user', $user->id, [
            'email' => $user->email,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Email verified successfully.',
        ]);
    }

    public function resendVerification(Request $request, EmailSettingsService $emailSettings, AuditLogService $audit): JsonResponse
    {
        $payload = $request->validate([
            'email' => ['required', 'email', 'max:150'],
        ]);

        $email = $this->normalizeEmail($payload['email']);
        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json([
                'status' => 'success',
                'message' => 'If this email exists, a verification email has been sent.',
            ]);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'status' => 'success',
                'message' => 'Email is already verified.',
            ]);
        }

        $token = $this->issueEmailVerificationToken($user, $emailSettings);
        $this->sendVerificationEmail($user, $token, $emailSettings);

        $audit->record($user, 'auth.email_verification_resent', 'user', $user->id, [
            'email' => $user->email,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Verification email sent.',
        ]);
    }

    public function oauthStart(string $provider, OAuthService $oauth)
    {
        $provider = strtolower(trim($provider));
        if (!in_array($provider, ['google', 'microsoft'], true)) {
            abort(404);
        }

        $config = $oauth->providerConfig($provider);
        if (!$config['enabled']) {
            return redirect('/?oauth_error=provider_disabled');
        }

        $state = Str::random(48);
        $nonce = Str::random(48);
        Cache::put('oauth_state_' . $state, [
            'provider' => $provider,
            'nonce' => $nonce,
            'issued_at' => now()->toISOString(),
        ], now()->addMinutes(10));
        $authUrl = $oauth->buildAuthorizeUrl($provider, $config, $state, $nonce);

        return redirect()->away($authUrl);
    }

    public function oauthCallback(Request $request, string $provider, OAuthService $oauth)
    {
        $provider = strtolower(trim($provider));
        if (!in_array($provider, ['google', 'microsoft'], true)) {
            abort(404);
        }

        if ($request->filled('error')) {
            return view('auth.oauth-complete', [
                'ok' => false,
                'message' => (string) $request->query('error_description', 'OAuth login failed.'),
                'token' => null,
            ]);
        }

        $state = (string) $request->query('state', '');
        $statePayload = Cache::pull('oauth_state_' . $state);
        if ($state === '' || !is_array($statePayload) || ($statePayload['provider'] ?? null) !== $provider) {
            return view('auth.oauth-complete', [
                'ok' => false,
                'message' => 'Invalid OAuth state.',
                'token' => null,
            ]);
        }

        $code = (string) $request->query('code', '');
        if ($code === '') {
            return view('auth.oauth-complete', [
                'ok' => false,
                'message' => 'Missing OAuth authorization code.',
                'token' => null,
            ]);
        }

        try {
            $config = $oauth->providerConfig($provider, true);
            $tokenData = $oauth->exchangeCode($provider, $code, $config);
            $profile = $oauth->fetchProfile($provider, $tokenData, $config, (string) ($statePayload['nonce'] ?? ''));
            $user = $oauth->upsertUser($provider, $profile);
            $token = app(JwtService::class)->createToken([
                'sub' => $user->id,
                'uid' => $user->uuid,
            ], $this->jwtTtl());

            return view('auth.oauth-complete', [
                'ok' => true,
                'message' => 'Inloggen gelukt. Je wordt doorgestuurd...',
                'token' => $token,
            ]);
        } catch (\Throwable $e) {
            return view('auth.oauth-complete', [
                'ok' => false,
                'message' => 'OAuth login failed.',
                'token' => null,
            ]);
        }
    }

    public function me(): JsonResponse
    {
        $user = request()->attributes->get('auth_user');

        return response()->json([
            'status' => 'success',
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(AuditLogService $audit): JsonResponse
    {
        $user = request()->attributes->get('auth_user');
        if ($user) {
            $audit->record($user, 'auth.logout', 'user', $user->id, [
                'email' => $user->email,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out. Please discard the token on the client.',
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'email' => $user->email,
            'phone' => $user->phone,
            'username' => $user->username,
            'role' => $user->app_role,
            'plan' => $user->plan,
            'plan_status' => $user->plan_status,
            'status' => $user->status,
            'email_verified_at' => $user->email_verified_at,
        ];
    }

    private function normalizeEmail(?string $email): ?string
    {
        $email = trim((string) $email);
        return $email === '' ? null : strtolower($email);
    }

    private function normalizePhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return null;
        }
        $normalized = preg_replace('/[^0-9+]/', '', $phone);
        return $normalized ?: null;
    }

    private function deriveUsername(?string $email, ?string $phone): string
    {
        if ($email) {
            return substr(explode('@', $email)[0], 0, 100);
        }
        if ($phone) {
            return substr('user_' . ltrim($phone, '+'), 0, 100);
        }
        return 'user_' . Str::random(8);
    }

    private function jwtTtl(): int
    {
        return (int) (env('JWT_TTL', 60));
    }

    private function verificationRequired(EmailSettingsService $emailSettings, ?string $email): bool
    {
        if (!$email) {
            return false;
        }
        $settings = $emailSettings->settings();
        return (bool) ($settings['email_enabled'] ?? false) && (bool) ($settings['email_send_verification'] ?? false);
    }

    private function issueEmailVerificationToken(User $user, EmailSettingsService $emailSettings): string
    {
        $settings = $emailSettings->settings();
        $ttl = max(5, (int) ($settings['email_verification_token_ttl_minutes'] ?? 1440));
        $plain = Str::random(64);

        $user->email_verification_token = hash('sha256', $plain);
        $user->email_verification_sent_at = now();
        $user->email_verification_expires_at = now()->addMinutes($ttl);
        $user->save();

        return $plain;
    }

    private function sendVerificationEmail(User $user, string $token, EmailSettingsService $emailSettings): void
    {
        $settings = $emailSettings->settings(true);
        if (!(bool) ($settings['email_enabled'] ?? false) || !(bool) ($settings['email_send_verification'] ?? false) || !$user->email) {
            return;
        }

        $baseUrl = $this->resolveVerificationBaseUrl($settings);
        $separator = str_contains($baseUrl, '?') ? '&' : '?';
        $link = $baseUrl . $separator . http_build_query(['uid' => $user->uuid, 'token' => $token]);

        $ttl = max(5, (int) ($settings['email_verification_token_ttl_minutes'] ?? 1440));
        $template = $emailSettings->renderTemplate('email_verification', [
            'name' => $user->username ?: 'gebruiker',
            'verification_link' => $link,
            'ttl_minutes' => $ttl,
        ]);
        $subject = $template['subject'] ?? 'Verifieer je e-mailadres';
        $body = $template['body'] ?? ('Klik op deze link om te verifiëren: ' . $link);

        try {
            $emailSettings->applyRuntimeMailConfig();
            Mail::raw($body, function ($message) use ($user, $subject, $settings) {
                $message->to($user->email)->subject($subject);
                if (!empty($settings['email_reply_to_address'])) {
                    $message->replyTo($settings['email_reply_to_address'], $settings['email_reply_to_name'] ?: null);
                }
            });
            $this->logEmailEvent([
                'template_key' => 'email_verification',
                'to_email' => $user->email,
                'subject' => $subject,
                'status' => 'SENT',
                'meta' => ['source' => 'auth_register'],
            ]);
        } catch (\Throwable $e) {
            $this->logEmailEvent([
                'template_key' => 'email_verification',
                'to_email' => $user->email,
                'subject' => $subject,
                'status' => 'FAILED',
                'error_message' => $e->getMessage(),
                'meta' => ['source' => 'auth_register'],
            ]);
        }
    }

    private function logEmailEvent(array $data): void
    {
        try {
            EmailLog::create($data);
        } catch (\Throwable $e) {
        }
    }

    private function resolveVerificationBaseUrl(array $settings): string
    {
        $configured = trim((string) ($settings['email_verification_link_base_url'] ?? ''));
        if ($configured !== '') {
            return $this->normalizeVerificationBaseUrl($configured);
        }

        if (app()->bound('request')) {
            $request = request();
            $host = strtolower((string) $request->getHost());
            if ($host !== '' && !in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
                return rtrim($request->getSchemeAndHttpHost(), '/') . '/verify-email';
            }
        }

        return rtrim((string) config('app.url'), '/') . '/verify-email';
    }

    private function normalizeVerificationBaseUrl(string $value): string
    {
        $value = trim($value);
        if (!preg_match('#^https?://#i', $value)) {
            $value = 'https://' . ltrim($value, '/');
        }

        $parts = parse_url($value);
        $path = $parts['path'] ?? '';
        if ($path === '' || $path === '/') {
            return rtrim($value, '/') . '/verify-email';
        }

        return $value;
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

}
