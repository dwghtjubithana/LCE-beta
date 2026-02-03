<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Company;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExpiryWatchdogTest extends TestCase
{
    use RefreshDatabase;

    public function test_expiry_watchdog_updates_status_and_notifies(): void
    {
        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'username' => 'watch',
            'email' => 'watch@example.com',
            'password_hash' => Hash::make('password123'),
            'app_role' => 'user',
            'status' => 'ACTIVE',
            'plan' => 'PRO',
            'plan_status' => 'ACTIVE',
        ]);

        $company = Company::create([
            'uuid' => (string) Str::uuid(),
            'owner_user_id' => $user->id,
            'company_name' => 'Watch Co',
            'sector' => 'Energy',
        ]);

        $expiringDoc = Document::create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'category_selected' => 'KKF Uittreksel',
            'status' => 'VALID',
            'extracted_data' => ['expiry_date' => now()->addDays(10)->format('Y-m-d')],
        ]);

        $expiredDoc = Document::create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'category_selected' => 'Vergunning',
            'status' => 'VALID',
            'extracted_data' => ['expiry_date' => now()->subDays(1)->format('Y-m-d')],
        ]);

        Artisan::call('lce:expiry-watchdog');

        $this->assertEquals('EXPIRING_SOON', $expiringDoc->fresh()->status);
        $this->assertEquals('EXPIRED', $expiredDoc->fresh()->status);
        $this->assertDatabaseHas('notifications', [
            'document_id' => $expiringDoc->id,
            'type' => 'EXPIRING_SOON',
        ]);
    }
}
