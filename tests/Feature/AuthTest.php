<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_login_me_flow(): void
    {
        $register = $this->postJson('/api/auth/register', [
            'email' => 'user@example.com',
            'password' => 'secret1234',
        ]);
        $register->assertStatus(201)->assertJsonStructure([
            'status',
            'token',
            'expires_in',
            'user' => ['id', 'uuid', 'email'],
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
            'password' => 'secret1234',
        ]);
        $login->assertStatus(200)->assertJsonStructure([
            'status',
            'token',
            'expires_in',
            'user' => ['id', 'uuid', 'email'],
        ]);

        $token = $login->json('token');
        $me = $this->getJson('/api/auth/me', [
            'Authorization' => 'Bearer ' . $token,
        ]);
        $me->assertStatus(200)->assertJsonStructure([
            'status',
            'user' => ['id', 'uuid', 'email'],
        ]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::create([
            'uuid' => (string) Str::uuid(),
            'username' => 'tester',
            'email' => 'tester@example.com',
            'password_hash' => Hash::make('password123'),
            'app_role' => 'user',
            'status' => 'ACTIVE',
            'plan' => 'FREE',
            'plan_status' => 'ACTIVE',
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'tester@example.com',
            'password' => 'wrong-password',
        ]);

        $login->assertStatus(401);
    }
}
