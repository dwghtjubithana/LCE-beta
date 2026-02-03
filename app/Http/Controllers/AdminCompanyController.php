<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminCompanyController extends Controller
{
    public function store(Request $request, AuditLogService $audit): JsonResponse
    {
        $data = $request->validate([
            'owner_user_id' => ['required', 'integer'],
            'company_name' => ['required', 'string', 'max:255'],
            'sector' => ['required', 'string', 'max:255'],
            'experience' => ['nullable', 'string'],
            'contact' => ['nullable'],
        ]);

        $company = Company::create([
            'uuid' => (string) Str::uuid(),
            'owner_user_id' => $data['owner_user_id'],
            'company_name' => $data['company_name'],
            'sector' => $data['sector'],
            'experience' => $data['experience'] ?? null,
            'contact' => $request->input('contact'),
            'bluewave_status' => false,
            'current_score' => 0,
            'verification_level' => 'unverified',
        ]);

        $audit->record($this->authUser(), 'admin.companies.create', 'company', $company->id, [
            'company_name' => $company->company_name,
        ]);

        return response()->json([
            'status' => 'success',
            'company' => $company,
        ], 201);
    }
    public function index(Request $request, AuditLogService $audit): JsonResponse
    {
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 ? min($limit, 100) : 20;
        $page = (int) $request->query('page', 1);
        $page = $page > 0 ? $page : 1;
        $search = trim((string) $request->query('search', ''));

        $query = Company::query()->orderByDesc('created_at');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('sector', 'like', "%{$search}%");
            });
        }

        $total = (clone $query)->count();
        $companies = $query->forPage($page, $limit)->get();

        $audit->record($this->authUser(), 'admin.companies.view', 'company', null, [
            'limit' => $limit,
            'page' => $page,
            'search' => $search ?: null,
        ]);

        return response()->json([
            'status' => 'success',
            'companies' => $companies,
            'meta' => [
                'page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => $limit > 0 ? (int) ceil($total / $limit) : 1,
            ],
        ]);
    }

    public function show(AuditLogService $audit, int $id): JsonResponse
    {
        $company = Company::find($id);
        if (!$company) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Company not found.',
            ], 404);
        }

        $audit->record($this->authUser(), 'admin.companies.view_one', 'company', $company->id);

        return response()->json([
            'status' => 'success',
            'company' => $company,
        ]);
    }

    private function authUser()
    {
        return request()->attributes->get('auth_user');
    }
}
