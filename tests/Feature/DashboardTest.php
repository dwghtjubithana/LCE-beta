<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Document;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_score_and_required_docs(): void
    {
        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'username' => 'dash',
            'email' => 'dash@example.com',
            'password_hash' => Hash::make('password123'),
            'app_role' => 'user',
            'status' => 'ACTIVE',
            'plan' => 'PRO',
            'plan_status' => 'ACTIVE',
        ]);

        $company = Company::create([
            'uuid' => (string) Str::uuid(),
            'owner_user_id' => $user->id,
            'company_name' => 'Dash Co',
            'sector' => 'Tech',
        ]);

        Document::create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'category_selected' => 'KKF Uittreksel',
            'status' => 'VALID',
        ]);

        $token = app(JwtService::class)->createToken(['sub' => $user->id, 'uid' => $user->uuid]);

        $response = $this->getJson('/api/companies/' . $company->id . '/dashboard', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('current_score', 25)
            ->assertJsonPath('score_color', 'Rood')
            ->assertJsonStructure([
                'status',
                'current_score',
                'score_color',
                'required_documents' => [
                    ['type', 'status'],
                ],
            ]);
    }
}
