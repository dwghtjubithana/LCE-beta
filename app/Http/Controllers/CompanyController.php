<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\ScoreService;
use App\Services\ProfilePdfService;
use App\Services\DocumentRequirementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function store(CreateCompanyRequest $request, AuditLogService $audit): JsonResponse
    {
        $user = $this->authUser();

        $payload = [
            'uuid' => (string) Str::uuid(),
            'owner_user_id' => $user->id,
            'company_name' => $request->input('company_name'),
            'sector' => $request->input('sector'),
            'experience' => $request->input('experience'),
            'contact' => $request->input('contact'),
            'bluewave_status' => false,
            'current_score' => 0,
            'verification_level' => 'unverified',
            'verification_status' => Company::VERIFICATION_UNVERIFIED,
            'compliance_gate_passed' => false,
        ];
        if ($this->companyTypeKeyAvailable()) {
            $payload['company_type_key'] = $request->input('company_type_key');
        }

        $company = Company::create($payload);

        $audit->record($user, 'company.create', 'company', $company->id, [
            'company_name' => $company->company_name,
        ]);

        return response()->json([
            'status' => 'success',
            'company' => $company,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->authUser();
        $company = Company::where('id', $id)
            ->where('owner_user_id', $user->id)
            ->first();

        if (!$company) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Company not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'company' => $company,
        ]);
    }

    public function update(UpdateCompanyRequest $request, AuditLogService $audit, int $id): JsonResponse
    {
        $user = $this->authUser();
        $company = Company::where('id', $id)
            ->where('owner_user_id', $user->id)
            ->first();

        if (!$company) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Company not found.',
            ], 404);
        }

        if ($request->filled('public_slug')) {
            $slug = $request->input('public_slug');
            $exists = Company::where('public_slug', $slug)
                ->where('id', '!=', $company->id)
                ->exists();
            if ($exists) {
                return response()->json([
                    'code' => 'SLUG_TAKEN',
                    'message' => 'Public slug already in use.',
                ], 422);
            }
        }

        $updatable = [
            'company_name',
            'sector',
            'experience',
            'contact',
            'bluewave_status',
            'verification_level',
            'public_slug',
            'display_name',
            'address',
            'lat',
            'lng',
        ];
        if ($this->companyTypeKeyAvailable()) {
            $updatable[] = 'company_type_key';
        }

        $company->fill($request->only($updatable));
        if ($request->filled('verification_level')) {
            $company->verification_status = $this->verificationBadge($request->input('verification_level'));
        }
        $company->save();

        $audit->record($user, 'company.update', 'company', $company->id, [
            'company_name' => $company->company_name,
        ]);

        return response()->json([
            'status' => 'success',
            'company' => $company,
        ]);
    }

    public function me(): JsonResponse
    {
        $user = $this->authUser();
        $company = Company::where('owner_user_id', $user->id)->first();

        if (!$company) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Company not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'company' => $company,
        ]);
    }

    public function dashboard(ScoreService $scores, DocumentRequirementService $requirements, int $id): JsonResponse
    {
        $user = $this->authUser();
        $company = Company::where('id', $id)
            ->where('owner_user_id', $user->id)
            ->first();

        if (!$company) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Company not found.',
            ], 404);
        }

        $currentLevel = $requirements->normalizeLevel((string) ($user->plan ?? 'FREE'));
        $result = $scores->calculate($company, $currentLevel);
        $company->current_score = $result['score'];
        $company->save();

        $states = [];
        foreach ($result['required_types'] as $type) {
            $states[] = [
                'type' => $type,
                'status' => $this->latestCategoryStatus($company->id, $type),
            ];
        }

        $checklistStates = [];
        foreach ($requirements->checklistTypesForCompany($company, $currentLevel) as $type) {
            $checklistStates[] = [
                'type' => $type === 'ID Bewijs' ? 'ID-kaart' : $type,
                'status' => $this->latestCategoryStatus($company->id, $type),
            ];
        }
        $levelProgress = $requirements->levelProgress($company, $currentLevel);
        $uploadCategories = $requirements->uploadCategoriesForCompany($company, $currentLevel);

        return response()->json([
            'status' => 'success',
            'current_score' => $company->current_score,
            'score_color' => $this->scoreColor($company->current_score),
            'verification_status' => $company->verification_status ?? Company::VERIFICATION_UNVERIFIED,
            'compliance_gate_passed' => (bool) $company->compliance_gate_passed,
            'current_level' => $currentLevel,
            'required_documents' => $states,
            'checklist_documents' => $checklistStates,
            'upload_categories' => $uploadCategories,
            'level_progress' => $levelProgress,
        ]);
    }

    public function dashboardMe(ScoreService $scores, DocumentRequirementService $requirements): JsonResponse
    {
        $user = $this->authUser();
        $company = Company::where('owner_user_id', $user->id)->first();

        if (!$company) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Company not found.',
            ], 404);
        }

        return $this->dashboard($scores, $requirements, $company->id);
    }

    public function profilePdf(ProfilePdfService $pdf, int $id)
    {
        $user = $this->authUser();
        $company = Company::where('id', $id)
            ->where('owner_user_id', $user->id)
            ->first();

        if (!$company) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Company not found.',
            ], 404);
        }

        $isFree = strtoupper((string) ($user->plan ?? 'FREE')) === 'FREE';
        return $pdf->download($company, $isFree);
    }

    public function publicProfile(string $slug): JsonResponse
    {
        $company = Company::where('public_slug', $slug)->first();
        if (!$company) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Company not found.',
            ], 404);
        }

        $photoUrl = $company->profile_photo_path
            ? url("/api/public/companies/{$company->public_slug}/photo")
            : null;

        return response()->json([
            'status' => 'success',
            'profile' => [
                'company_name' => $company->company_name,
                'display_name' => $company->display_name,
                'sector' => $company->sector,
                'address' => $company->address,
                'lat' => $company->lat,
                'lng' => $company->lng,
                'verification_status' => $company->verification_status ?? 'GRAY',
                'photo_url' => $photoUrl,
                'contact' => $company->contact,
            ],
        ]);
    }

    public function uploadProfilePhoto(\App\Http\Requests\UploadProfilePhotoRequest $request): JsonResponse
    {
        $user = $this->authUser();
        $company = Company::where('owner_user_id', $user->id)->first();
        if (!$company) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Company not found.',
            ], 404);
        }

        $file = $request->file('file');
        if (!$file || !$file->isValid()) {
            return response()->json([
                'code' => 'INVALID_FILE',
                'message' => 'Uploaded file is not valid.',
            ], 422);
        }

        $path = $file->store('profile-photos', 'public');
        $company->profile_photo_path = $path;
        $company->save();

        return response()->json([
            'status' => 'success',
            'photo_url' => url('/api/companies/me/profile-photo'),
        ]);
    }

    public function profilePhotoMe()
    {
        $user = $this->authUser();
        $company = Company::where('owner_user_id', $user->id)->first();
        if (!$company || !$company->profile_photo_path) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Photo not found.',
            ], 404);
        }

        $path = storage_path('app/public/' . $company->profile_photo_path);
        if (!is_file($path)) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Photo not found.',
            ], 404);
        }

        return response()->file($path);
    }

    public function publicPhoto(string $slug)
    {
        $company = Company::where('public_slug', $slug)->first();
        if (!$company || !$company->profile_photo_path) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Photo not found.',
            ], 404);
        }

        $path = storage_path('app/public/' . $company->profile_photo_path);
        if (!is_file($path)) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Photo not found.',
            ], 404);
        }

        return response()->file($path);
    }

    public function slugCheck(): JsonResponse
    {
        $slug = trim((string) request()->query('slug', ''));
        if ($slug === '') {
            return response()->json([
                'code' => 'INVALID_SLUG',
                'message' => 'Slug is required.',
            ], 422);
        }

        $user = $this->authUser();
        $company = Company::where('owner_user_id', $user->id)->first();
        $exists = Company::where('public_slug', $slug)
            ->when($company, fn ($q) => $q->where('id', '!=', $company->id))
            ->exists();

        return response()->json([
            'status' => 'success',
            'available' => !$exists,
        ]);
    }

    public function geocode(): JsonResponse
    {
        $address = trim((string) request()->input('address', ''));
        if ($address === '') {
            return response()->json([
                'code' => 'INVALID_ADDRESS',
                'message' => 'Address is required.',
            ], 422);
        }

        $res = Http::get('https://nominatim.openstreetmap.org/search', [
            'q' => $address,
            'format' => 'json',
            'limit' => 1,
        ]);

        if (!$res->ok()) {
            return response()->json([
                'code' => 'GEOCODE_FAILED',
                'message' => 'Geocode failed.',
            ], 502);
        }

        $data = $res->json();
        $item = is_array($data) && count($data) ? $data[0] : null;
        if (!$item || !isset($item['lat'], $item['lon'])) {
            return response()->json([
                'code' => 'GEOCODE_NOT_FOUND',
                'message' => 'No location found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'lat' => $item['lat'],
            'lng' => $item['lon'],
        ]);
    }

    public function profilePdfMe(ProfilePdfService $pdf)
    {
        $user = $this->authUser();
        $company = Company::where('owner_user_id', $user->id)->first();

        if (!$company) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Company not found.',
            ], 404);
        }

        $isFree = strtoupper((string) ($user->plan ?? 'FREE')) === 'FREE';
        return $pdf->download($company, $isFree);
    }

    private function authUser(): User
    {
        return request()->attributes->get('auth_user');
    }

    private function latestCategoryStatus(int $companyId, string $category): string
    {
        $doc = \App\Models\Document::where('company_id', $companyId)
            ->where('category_selected', $category)
            ->orderByDesc('id')
            ->first();

        return $doc->status ?? 'MISSING';
    }

    private function scoreColor(int $score): string
    {
        if ($score >= 100) {
            return 'Groen';
        }
        if ($score >= 50) {
            return 'Oranje';
        }
        return 'Rood';
    }

    private function verificationBadge(?string $level): string
    {
        return $level === 'physical_verified' ? 'GOLD' : 'GRAY';
    }

    private function companyTypeKeyAvailable(): bool
    {
        try {
            return Schema::hasColumn('companies', 'company_type_key');
        } catch (\Throwable $e) {
            return false;
        }
    }
}
