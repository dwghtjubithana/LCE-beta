<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Str;

class JwtService
{
    public function createToken(array $claims, int $ttlMinutes = 60): string
    {
        $now = time();
        $payload = array_merge([
            'iss' => config('app.url'),
            'iat' => $now,
            'exp' => $now + ($ttlMinutes * 60),
            'jti' => (string) Str::uuid(),
        ], $claims);

        return JWT::encode($payload, $this->getSecret(), 'HS256');
    }

    public function decode(string $token): object
    {
        return JWT::decode($token, new Key($this->getSecret(), 'HS256'));
    }

    private function getSecret(): string
    {
        $secret = env('JWT_SECRET') ?: config('app.key');
        if (str_starts_with($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }
        return $secret;
    }
}
