<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = env('ADMIN_BASIC_USER');
        $pass = env('ADMIN_BASIC_PASS');

        if (!$user || !$pass) {
            return $next($request);
        }

        $auth = $request->header('Authorization', '');
        if (!str_starts_with($auth, 'Basic ')) {
            return $this->unauthorized();
        }

        $decoded = base64_decode(substr($auth, 6));
        if (!$decoded || !str_contains($decoded, ':')) {
            return $this->unauthorized();
        }

        [$inputUser, $inputPass] = explode(':', $decoded, 2);
        if ($inputUser !== $user || $inputPass !== $pass) {
            return $this->unauthorized();
        }

        return $next($request);
    }

    private function unauthorized(): Response
    {
        return response('Unauthorized', 401, ['WWW-Authenticate' => 'Basic']);
    }
}
