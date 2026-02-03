<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Document;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_and_list_documents(): void
    {
        Storage::fake('local');

        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'username' => 'uploader',
            'email' => 'uploader@example.com',
            'password_hash' => Hash::make('password123'),
            'app_role' => 'user',
            'status' => 'ACTIVE',
            'plan' => 'PRO',
            'plan_status' => 'ACTIVE',
        ]);

        $company = Company::create([
            'uuid' => (string) Str::uuid(),
            'owner_user_id' => $user->id,
            'company_name' => 'Uploader Co',
            'sector' => 'Tech',
        ]);

        $token = app(JwtService::class)->createToken(['sub' => $user->id, 'uid' => $user->uuid]);

        $file = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');
        $upload = $this->postJson('/api/documents/upload', [
            'file' => $file,
            'category_selected' => 'KKF Uittreksel',
            'company_id' => $company->id,
        ], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $upload->assertStatus(201)->assertJsonStructure([
            'status',
            'document' => ['id', 'status'],
        ]);

        $list = $this->getJson('/api/companies/' . $company->id . '/documents', [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $list->assertStatus(200)->assertJsonStructure([
            'status',
            'documents' => [
                ['id', 'status', 'ui_label', 'recommended_action', 'color'],
            ],
        ]);
    }

    public function test_bulk_upload_documents(): void
    {
        Storage::fake('local');

        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'username' => 'bulk',
            'email' => 'bulk@example.com',
            'password_hash' => Hash::make('password123'),
            'app_role' => 'user',
            'status' => 'ACTIVE',
            'plan' => 'PRO',
            'plan_status' => 'ACTIVE',
        ]);

        $company = Company::create([
            'uuid' => (string) Str::uuid(),
            'owner_user_id' => $user->id,
            'company_name' => 'Bulk Co',
            'sector' => 'Tech',
        ]);

        $token = app(JwtService::class)->createToken(['sub' => $user->id, 'uid' => $user->uuid]);

        $files = [
            UploadedFile::fake()->create('doc1.pdf', 50, 'application/pdf'),
            UploadedFile::fake()->create('doc2.pdf', 50, 'application/pdf'),
        ];

        $response = $this->postJson('/api/documents/upload/bulk', [
            'files' => $files,
            'category_selected' => 'KKF Uittreksel',
            'company_id' => $company->id,
        ], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(201)->assertJsonStructure([
            'status',
            'results' => [
                ['filename', 'status', 'color'],
            ],
        ]);
    }

    public function test_duplicate_upload_returns_conflict(): void
    {
        Storage::fake('local');

        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'username' => 'dup',
            'email' => 'dup@example.com',
            'password_hash' => Hash::make('password123'),
            'app_role' => 'user',
            'status' => 'ACTIVE',
            'plan' => 'PRO',
            'plan_status' => 'ACTIVE',
        ]);

        $company = Company::create([
            'uuid' => (string) Str::uuid(),
            'owner_user_id' => $user->id,
            'company_name' => 'Dup Co',
            'sector' => 'Tech',
        ]);

        $token = app(JwtService::class)->createToken(['sub' => $user->id, 'uid' => $user->uuid]);

        $file = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');
        $this->postJson('/api/documents/upload', [
            'file' => $file,
            'category_selected' => 'KKF Uittreksel',
            'company_id' => $company->id,
        ], [
            'Authorization' => 'Bearer ' . $token,
        ])->assertStatus(201);

        $file2 = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');
        $samePath = $file->getPathname();
        $dupPath = $file2->getPathname();
        if (is_file($samePath) && is_file($dupPath)) {
            copy($samePath, $dupPath);
        }
        $dup = $this->postJson('/api/documents/upload', [
            'file' => $file2,
            'category_selected' => 'KKF Uittreksel',
            'company_id' => $company->id,
        ], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $dup->assertStatus(409);
    }

    public function test_status_mapping_includes_label_and_action(): void
    {
        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'username' => 'mapper',
            'email' => 'mapper@example.com',
            'password_hash' => Hash::make('password123'),
            'app_role' => 'user',
            'status' => 'ACTIVE',
            'plan' => 'PRO',
            'plan_status' => 'ACTIVE',
        ]);

        $company = Company::create([
            'uuid' => (string) Str::uuid(),
            'owner_user_id' => $user->id,
            'company_name' => 'Mapper Co',
            'sector' => 'Tech',
        ]);

        $doc = Document::create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'category_selected' => 'KKF Uittreksel',
            'status' => 'INVALID',
        ]);

        $token = app(JwtService::class)->createToken(['sub' => $user->id, 'uid' => $user->uuid]);
        $response = $this->getJson('/api/documents/' . $doc->id, [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('document.ui_label', 'Invalid')
            ->assertJsonPath('document.recommended_action', 'Fix met AI')
            ->assertJsonPath('document.color', 'Rood');
    }

    public function test_expiry_date_column_set_from_extracted_data(): void
    {
        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'username' => 'expiry',
            'email' => 'expiry@example.com',
            'password_hash' => Hash::make('password123'),
            'app_role' => 'user',
            'status' => 'ACTIVE',
            'plan' => 'PRO',
            'plan_status' => 'ACTIVE',
        ]);

        $company = Company::create([
            'uuid' => (string) Str::uuid(),
            'owner_user_id' => $user->id,
            'company_name' => 'Expiry Co',
            'sector' => 'Tech',
        ]);

        $doc = Document::create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'category_selected' => 'KKF Uittreksel',
            'status' => 'PROCESSING',
            'extracted_data' => ['expiry_date' => '31-12-2025'],
        ]);

        \App\Jobs\ProcessDocument::dispatchSync($doc->id);
        $this->assertEquals('2025-12-31', $doc->fresh()->expiry_date?->format('Y-m-d'));
    }

    public function test_classifier_mismatch_sets_invalid(): void
    {
        Storage::fake('local');

        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'username' => 'classify',
            'email' => 'classify@example.com',
            'password_hash' => Hash::make('password123'),
            'app_role' => 'user',
            'status' => 'ACTIVE',
            'plan' => 'PRO',
            'plan_status' => 'ACTIVE',
        ]);

        $company = Company::create([
            'uuid' => (string) Str::uuid(),
            'owner_user_id' => $user->id,
            'company_name' => 'Classify Co',
            'sector' => 'Tech',
        ]);

        $token = app(JwtService::class)->createToken(['sub' => $user->id, 'uid' => $user->uuid]);

        $file = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');
        $upload = $this->postJson('/api/documents/upload', [
            'file' => $file,
            'category_selected' => 'KKF Uittreksel',
            'company_id' => $company->id,
            'ocr_text' => 'This is a Tax statement for Belastingverklaring',
        ], [
            'Authorization' => 'Bearer ' . $token,
        ]);

        $upload->assertStatus(201);

        $docId = $upload->json('document.id');
        \App\Jobs\ProcessDocument::dispatchSync($docId);

        $doc = Document::find($docId);
        $this->assertEquals('INVALID', $doc->status);
    }
}
