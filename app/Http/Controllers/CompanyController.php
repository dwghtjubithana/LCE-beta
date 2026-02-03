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

        $company->fill($request->only([
            'company_name',
            'sector',
            'experience',
            'contact',
            'bluewave_status',
            'verification_level',
        ]));
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
}
