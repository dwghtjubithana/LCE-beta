<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\ScoreService;
use App\Services\ProfilePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function store(CreateCompanyRequest $request, AuditLogService $audit): JsonResponse
    {
        $user = $this->authUser();

        $company = Company::create([
            'uuid' => (string) Str::uuid(),
            'owner_user_id' => $user->id,
            'company_name' => $request->input('company_name'),
            'sector' => $request->input('sector'),
            'experience' => $request->input('experience'),
            'contact' => $request->input('contact'),
            'bluewave_status' => false,
            'current_score' => 0,
            'verification_level' => 'unverified',
        ]);

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

        $company->fill($request->only([
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
        ]));
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

    public function dashboard(ScoreService $scores, int $id): JsonResponse
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

        $result = $scores->calculate($company);
        $company->current_score = $result['score'];
        $company->save();

        $states = [];
        foreach ($result['required_types'] as $type) {
            $doc = $company->id
                ? \App\Models\Document::where('company_id', $company->id)
                    ->where('category_selected', $type)
                    ->orderByDesc('id')
                    ->first()
                : null;
            $states[] = [
                'type' => $type,
                'status' => $doc->status ?? 'MISSING',
            ];
        }

        return response()->json([
            'status' => 'success',
            'current_score' => $company->current_score,
            'score_color' => $this->scoreColor($company->current_score),
            'required_documents' => $states,
        ]);
    }

    public function dashboardMe(ScoreService $scores): JsonResponse
    {
        $user = $this->authUser();
        $company = Company::where('owner_user_id', $user->id)->first();

        if (!$company) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Company not found.',
            ], 404);
        }

        return $this->dashboard($scores, $company->id);
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

        return $pdf->download($company);
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
            ? Storage::disk('public')->url($company->profile_photo_path)
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
            'photo_url' => Storage::disk('public')->url($path),
        ]);
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

        return $pdf->download($company);
    }

    private function authUser(): User
    {
        return request()->attributes->get('auth_user');
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
}
