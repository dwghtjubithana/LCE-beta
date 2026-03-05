<?php

namespace App\Http\Controllers;

use App\Models\PlanCatalog;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminPlanCatalogController extends Controller
{
    public function index(): JsonResponse
    {
        $includeInactive = in_array((string) request()->query('include_inactive', '1'), ['1', 'true', 'TRUE'], true);
        $query = PlanCatalog::query()->orderBy('rank')->orderBy('id');
        if (!$includeInactive) {
            $query->where('is_active', true);
        }

        return response()->json([
            'status' => 'success',
            'plans' => $query->get(),
        ]);
    }

    public function store(Request $request, AuditLogService $audit): JsonResponse
    {
        $data = $this->validated($request);
        $plan = PlanCatalog::create($data);
        $this->ensureSingleDefault($plan);

        $audit->record($this->authUser(), 'admin.plan_catalog.create', 'plan_catalog', $plan->id, [
            'plan_key' => $plan->plan_key,
        ]);

        return response()->json([
            'status' => 'success',
            'plan' => $plan,
        ], 201);
    }

    public function update(Request $request, AuditLogService $audit, int $id): JsonResponse
    {
        $plan = PlanCatalog::find($id);
        if (!$plan) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Plan not found.',
            ], 404);
        }

        $data = $this->validated($request, false, $id);
        $plan->fill($data);
        $plan->save();
        $this->ensureSingleDefault($plan);

        $audit->record($this->authUser(), 'admin.plan_catalog.update', 'plan_catalog', $plan->id, [
            'plan_key' => $plan->plan_key,
            'updated_fields' => array_keys($data),
        ]);

        return response()->json([
            'status' => 'success',
            'plan' => $plan,
        ]);
    }

    private function validated(Request $request, bool $isCreate = true, ?int $id = null): array
    {
        $rules = [
            'plan_key' => [
                $isCreate ? 'required' : 'sometimes',
                'string',
                'max:40',
                'regex:/^[A-Z0-9_]+$/',
                Rule::unique('plan_catalog', 'plan_key')->ignore($id),
            ],
            'plan_label' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'rank' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'available_for_signup' => ['nullable', 'boolean'],
            'available_for_upgrade' => ['nullable', 'boolean'],
            'requires_payment_proof' => ['nullable', 'boolean'],
        ];
        $data = $request->validate($rules);

        if (array_key_exists('plan_key', $data)) {
            $data['plan_key'] = strtoupper(trim((string) $data['plan_key']));
        }

        return $data;
    }

    private function ensureSingleDefault(PlanCatalog $plan): void
    {
        if (!$plan->is_default) {
            if (!PlanCatalog::query()->where('is_default', true)->exists()) {
                $plan->is_default = true;
                $plan->save();
            }
            return;
        }

        PlanCatalog::query()
            ->where('id', '!=', $plan->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    private function authUser()
    {
        return request()->attributes->get('auth_user');
    }
}
