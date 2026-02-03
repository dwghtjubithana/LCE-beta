<?php

namespace Tests\Feature;

use App\Models\Tender;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_user_is_forbidden(): void
    {
        putenv('ADMIN_ENFORCE_API=true');
        $_ENV['ADMIN_ENFORCE_API'] = 'true';
        $_SERVER['ADMIN_ENFORCE_API'] = 'true';

        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'username' => 'freeuser',
            'email' => 'free@example.com',
            'password_hash' => Hash::make('password123'),
            'app_role' => 'user',
            'status' => 'ACTIVE',
            'plan' => 'FREE',
            'plan_status' => 'ACTIVE',
        ]);

        $tender = Tender::create([
            'title' => 'Bridge Maintenance',
            'project' => 'Bridge Maintenance',
            'date' => now()->format('Y-m-d'),
            'client' => 'Gov',
            'details_url' => 'https://example.com/tender/bridge',
            'attachments' => ['spec.pdf'],
            'description' => 'Full tender description.',
        ]);

        $token = app(JwtService::class)->createToken(['sub' => $user->id, 'uid' => $user->uuid]);
        $response = $this->getJson('/api/tenders/' . $tender->id, [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(403);

        putenv('ADMIN_ENFORCE_API=false');
        $_ENV['ADMIN_ENFORCE_API'] = 'false';
        $_SERVER['ADMIN_ENFORCE_API'] = 'false';
    }

    public function test_business_user_sees_full_fields(): void
    {
        putenv('ADMIN_ENFORCE_API=true');
        $_ENV['ADMIN_ENFORCE_API'] = 'true';
        $_SERVER['ADMIN_ENFORCE_API'] = 'true';

        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'username' => 'bizuser',
            'email' => 'biz@example.com',
            'password_hash' => Hash::make('password123'),
            'app_role' => 'admin',
            'status' => 'ACTIVE',
            'plan' => 'BUSINESS',
            'plan_status' => 'ACTIVE',
        ]);

        $tender = Tender::create([
            'title' => 'Solar Project',
            'project' => 'Solar Project',
            'date' => now()->format('Y-m-d'),
            'client' => 'Energy',
            'details_url' => 'https://example.com/tender/solar',
            'attachments' => ['rfi.pdf'],
            'description' => 'Full tender description.',
        ]);

        $token = app(JwtService::class)->createToken(['sub' => $user->id, 'uid' => $user->uuid]);
        $response = $this->getJson('/api/tenders/' . $tender->id, [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('tender.details_url', 'https://example.com/tender/solar')
            ->assertJsonPath('tender.attachments.0', 'rfi.pdf');

        putenv('ADMIN_ENFORCE_API=false');
        $_ENV['ADMIN_ENFORCE_API'] = 'false';
        $_SERVER['ADMIN_ENFORCE_API'] = 'false';
    }
}
