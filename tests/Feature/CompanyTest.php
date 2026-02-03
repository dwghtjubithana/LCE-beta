<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_read_company(): void
    {
        $user = $this->makeUser();
        $token = app(JwtService::class)->createToken(['sub' => $user->id, 'uid' => $user->uuid]);

        $create = $this->postJson('/api/companies', [
            'company_name' => 'Acme Inc',
            'sector' => 'Construction',
            'experience' => '5 years',
            'contact' => ['email' => 'contact@acme.com'],
        ], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $create->assertStatus(201)->assertJsonPath('company.company_name', 'Acme Inc');

        $companyId = $create->json('company.id');

        $get = $this->getJson('/api/companies/' . $companyId, [
            'Authorization' => 'Bearer ' . $token,
        ]);
        $get->assertStatus(200)->assertJsonPath('company.id', $companyId);
    }

    public function test_user_cannot_access_other_company(): void
    {
        $owner = $this->makeUser('owner@example.com', 'owner');
        $other = $this->makeUser('other@example.com', 'other');

        $company = Company::create([
            'uuid' => (string) Str::uuid(),
            'owner_user_id' => $owner->id,
            'company_name' => 'Owner Co',
            'sector' => 'Energy',
        ]);

        $token = app(JwtService::class)->createToken(['sub' => $other->id, 'uid' => $other->uuid]);
        $get = $this->getJson('/api/companies/' . $company->id, [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $get->assertStatus(404);
    }

    public function test_admin_enforcement_blocks_non_admin(): void
    {
        putenv('ADMIN_ENFORCE_API=true');
        $_ENV['ADMIN_ENFORCE_API'] = 'true';
        $_SERVER['ADMIN_ENFORCE_API'] = 'true';

        $user = $this->makeUser('basic@example.com', 'basic');
        $token = app(JwtService::class)->createToken(['sub' => $user->id, 'uid' => $user->uuid]);

        $response = $this->getJson('/api/companies/me', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(403);

        putenv('ADMIN_ENFORCE_API=false');
        $_ENV['ADMIN_ENFORCE_API'] = 'false';
        $_SERVER['ADMIN_ENFORCE_API'] = 'false';
    }

    private function makeUser(string $email = 'user@example.com', string $username = 'user'): User
    {
        return User::create([
            'uuid' => (string) Str::uuid(),
            'username' => $username,
            'email' => $email,
            'password_hash' => Hash::make('password123'),
            'app_role' => 'user',
            'status' => 'ACTIVE',
            'plan' => 'PRO',
            'plan_status' => 'ACTIVE',
        ]);
    }
}
