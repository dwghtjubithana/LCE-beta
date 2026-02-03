<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_endpoints_block_non_admin_when_enforced(): void
    {
        $this->setAdminEnforcement(true);
        $user = $this->makeUser('user', 'user@example.com');
        $token = $this->tokenFor($user);

        $this->getJson('/api/admin/users', $this->authHeader($token))->assertStatus(403);
        $this->getJson('/api/admin/compliance-rules', $this->authHeader($token))->assertStatus(403);
        $this->getJson('/api/admin/tenders', $this->authHeader($token))->assertStatus(403);
        $this->getJson('/api/admin/notifications', $this->authHeader($token))->assertStatus(403);
        $this->getJson('/api/admin/health', $this->authHeader($token))->assertStatus(403);
        $this->getJson('/api/admin/metrics', $this->authHeader($token))->assertStatus(403);
    }

    public function test_admin_endpoints_allow_admin_when_enforced(): void
    {
        $this->setAdminEnforcement(true);
        $admin = $this->makeUser('admin', 'admin@example.com');
        $token = $this->tokenFor($admin);

        $rule = $this->postJson('/api/admin/compliance-rules', [
            'document_type' => 'Test Doc',
            'max_age_months' => 12,
        ], $this->authHeader($token));
        $rule->assertStatus(201);
        $ruleId = $rule->json('rule.id');

        $updateRule = $this->patchJson('/api/admin/compliance-rules/' . $ruleId, [
            'max_age_months' => 6,
        ], $this->authHeader($token));
        $updateRule->assertStatus(200)->assertJsonPath('rule.max_age_months', 6);

        $tender = $this->postJson('/api/admin/tenders', [
            'title' => 'Bridge Repair',
            'client' => 'City Works',
        ], $this->authHeader($token));
        $tender->assertStatus(201);

        $targetUser = $this->makeUser('user', 'target@example.com');
        $update = $this->patchJson('/api/admin/users/' . $targetUser->id, [
            'plan' => 'PRO',
            'plan_status' => 'ACTIVE',
        ], $this->authHeader($token));
        $update->assertStatus(200)->assertJsonPath('user.plan', 'PRO');

        $notification = AppNotification::create([
            'user_id' => $targetUser->id,
            'type' => 'EXPIRING_SOON',
            'channel' => 'email',
        ]);

        $resend = $this->postJson('/api/admin/notifications/' . $notification->id . '/resend', [], $this->authHeader($token));
        $resend->assertStatus(200);

        AppNotification::create([
            'user_id' => $targetUser->id,
            'type' => 'EXPIRING_SOON',
            'channel' => 'email',
        ]);

        $bulk = $this->postJson('/api/admin/notifications/mark-sent', [
            'status' => 'pending',
        ], $this->authHeader($token));
        $bulk->assertStatus(200)->assertJsonPath('updated', 1);

        $deleteRule = $this->deleteJson('/api/admin/compliance-rules/' . $ruleId, [], $this->authHeader($token));
        $deleteRule->assertStatus(200);

        $this->getJson('/api/admin/notifications', $this->authHeader($token))->assertStatus(200);
        $this->getJson('/api/admin/users', $this->authHeader($token))->assertStatus(200);
        $this->getJson('/api/admin/compliance-rules', $this->authHeader($token))->assertStatus(200);
        $this->getJson('/api/admin/tenders', $this->authHeader($token))->assertStatus(200);
        $this->getJson('/api/admin/health', $this->authHeader($token))->assertStatus(200);
        $this->getJson('/api/admin/metrics', $this->authHeader($token))->assertStatus(200);
    }

    private function tokenFor(User $user): string
    {
        return app(JwtService::class)->createToken(['sub' => $user->id]);
    }

    private function authHeader(string $token): array
    {
        return ['Authorization' => 'Bearer ' . $token];
    }

    private function makeUser(string $role, string $email): User
    {
        return User::create([
            'uuid' => (string) Str::uuid(),
            'username' => $role,
            'email' => $email,
            'password_hash' => Hash::make('password123'),
            'app_role' => $role,
            'status' => 'ACTIVE',
            'plan' => 'FREE',
            'plan_status' => 'ACTIVE',
        ]);
    }

    private function setAdminEnforcement(bool $enabled): void
    {
        $value = $enabled ? 'true' : 'false';
        putenv("ADMIN_ENFORCE_API={$value}");
        $_ENV['ADMIN_ENFORCE_API'] = $value;
        $_SERVER['ADMIN_ENFORCE_API'] = $value;
    }
}
