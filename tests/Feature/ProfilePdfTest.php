<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProfilePdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_pdf_download(): void
    {
        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'username' => 'pdfuser',
            'email' => 'pdf@example.com',
            'password_hash' => Hash::make('password123'),
            'app_role' => 'user',
            'status' => 'ACTIVE',
            'plan' => 'PRO',
            'plan_status' => 'ACTIVE',
        ]);

        $company = Company::create([
            'uuid' => (string) Str::uuid(),
            'owner_user_id' => $user->id,
            'company_name' => 'PDF Co',
            'sector' => 'Energy',
            'experience' => '10 years',
            'contact' => ['email' => 'info@pdfco.com'],
        ]);

        $token = app(JwtService::class)->createToken(['sub' => $user->id, 'uid' => $user->uuid]);

        $response = $this->get('/api/companies/' . $company->id . '/profile.pdf', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-type') ?? '', 'application/pdf'));
    }
}
