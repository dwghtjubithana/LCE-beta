<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('admin.basic_enabled', true)) {
            return $next($request);
        }

        $user = trim((string) config('admin.basic_user', ''));
        $pass = (string) config('admin.basic_pass', '');

        if (!$user || !$pass) {
            if (app()->environment('production')) {
                return response('Admin basic auth is misconfigured.', 503);
            }
            return $next($request);
        }

        [$inputUser, $inputPass] = $this->extractCredentials($request);
        if ($inputUser === null || $inputPass === null) {
            return $this->unauthorized();
        }

        if (!hash_equals($user, $inputUser) || !hash_equals($pass, $inputPass)) {
            return $this->unauthorized();
        }

        return $next($request);
    }

    private function extractCredentials(Request $request): array
    {
        // Most web servers populate these for HTTP Basic authentication.
        $user = $request->getUser();
        $pass = $request->getPassword();
        if ($user !== null && $pass !== null) {
            return [$user, $pass];
        }

        // Fallback for environments where Authorization header is forwarded directly.
        $auth = (string) $request->header('Authorization', '');
        if (!str_starts_with($auth, 'Basic ')) {
            return [null, null];
        }

        $decoded = base64_decode(substr($auth, 6), true);
        if (!is_string($decoded) || !str_contains($decoded, ':')) {
            return [null, null];
        }

        [$inputUser, $inputPass] = explode(':', $decoded, 2);
        return [$inputUser, $inputPass];
    }

    private function unauthorized(): Response
    {
        return response('Unauthorized', 401, ['WWW-Authenticate' => 'Basic realm="Admin"']);
    }
}
