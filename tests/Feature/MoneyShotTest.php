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

class MoneyShotTest extends TestCase
{
    use RefreshDatabase;

    public function test_money_shot_flow(): void
    {
        $register = $this->postJson('/api/auth/register', [
            'email' => 'money@example.com',
            'password' => 'secret1234',
        ]);
        $register->assertStatus(201);

        $token = $register->json('token');

        $company = $this->postJson('/api/companies', [
            'company_name' => 'Money Co',
            'sector' => 'Energy',
        ], [
            'Authorization' => 'Bearer ' . $token,
        ]);
        $company->assertStatus(201);

        $companyId = $company->json('company.id');

        $docs = [
            'KKF Uittreksel',
            'Vergunning',
            'Belastingverklaring',
            'ID Bewijs',
        ];

        foreach ($docs as $docType) {
            Document::create([
                'uuid' => (string) Str::uuid(),
                'company_id' => $companyId,
                'category_selected' => $docType,
                'status' => 'VALID',
            ]);
        }

        $dashboard = $this->getJson('/api/companies/' . $companyId . '/dashboard', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $dashboard->assertStatus(200)
            ->assertJsonPath('current_score', 100);
    }
}
