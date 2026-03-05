<?php

namespace App\Http\Controllers;

use App\Models\ComplianceRule;
use App\Models\PlanCatalog;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ComplianceRuleController extends Controller
{
    public function index(Request $request, AuditLogService $audit): JsonResponse
    {
        $limit = (int) $request->query('limit', 50);
        $limit = $limit > 0 ? min($limit, 100) : 50;
        $page = (int) $request->query('page', 1);
        $page = $page > 0 ? $page : 1;
        $search = trim((string) $request->query('search', ''));

        $query = ComplianceRule::query()->orderBy('document_type');
        if ($search !== '') {
            $query->where('document_type', 'like', "%{$search}%");
        }

        $total = (clone $query)->count();
        $rules = $query->forPage($page, $limit)->get();

        $audit->record($this->authUser(), 'admin.compliance_rules.view', 'compliance_rule', null, [
            'limit' => $limit,
            'page' => $page,
            'search' => $search ?: null,
        ]);

        return response()->json([
            'status' => 'success',
            'rules' => $rules,
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
        $rule = ComplianceRule::find($id);
        if (!$rule) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Compliance rule not found.',
            ], 404);
        }

        $audit->record($this->authUser(), 'admin.compliance_rules.view_one', 'compliance_rule', $rule->id);

        return response()->json([
            'status' => 'success',
            'rule' => $rule,
        ]);
    }

    public function meta(): JsonResponse
    {
        $levels = $this->availablePlanKeys();
        $sectors = ['general'];

        try {
            $sectorRows = ComplianceRule::query()
                ->whereNotNull('sector_applicability')
                ->pluck('sector_applicability')
                ->all();
            foreach ($sectorRows as $row) {
                $decoded = is_array($row) ? $row : json_decode((string) $row, true);
                if (!is_array($decoded)) {
                    continue;
                }
                foreach ($decoded as $item) {
                    $key = strtolower(trim((string) $item));
                    if ($key !== '' && !in_array($key, $sectors, true)) {
                        $sectors[] = $key;
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        $companyTypes = [];
        try {
            if (Schema::hasTable('company_type_requirements')) {
                $companyTypes = DB::table('company_type_requirements')
                    ->orderBy('company_type_label')
                    ->get(['company_type_key', 'company_type_label', 'requires_bedrijfsvergunning'])
                    ->map(function ($row) {
                        return [
                            'key' => (string) ($row->company_type_key ?? ''),
                            'label' => (string) ($row->company_type_label ?? ''),
                            'requires_bedrijfsvergunning' => (bool) ($row->requires_bedrijfsvergunning ?? false),
                        ];
                    })
                    ->values()
                    ->all();
            }
        } catch (\Throwable $e) {
            $companyTypes = [];
        }

        return response()->json([
            'status' => 'success',
            'meta' => [
                'levels' => $levels,
                'sectors' => $sectors,
                'company_types' => $companyTypes,
            ],
        ]);
    }

    public function store(Request $request, AuditLogService $audit): JsonResponse
    {
        $data = $this->validated($request, true);
        $rule = ComplianceRule::create($data);

        $audit->record($this->authUser(), 'compliance_rule.create', 'compliance_rule', $rule->id, [
            'document_type' => $rule->document_type,
        ]);

        return response()->json([
            'status' => 'success',
            'rule' => $rule,
        ], 201);
    }

    public function update(Request $request, AuditLogService $audit, int $id): JsonResponse
    {
        $rule = ComplianceRule::find($id);
        if (!$rule) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Compliance rule not found.',
            ], 404);
        }

        $data = $this->validated($request, false);
        $rule->fill($data);
        $rule->save();

        $audit->record($this->authUser(), 'compliance_rule.update', 'compliance_rule', $rule->id, [
            'document_type' => $rule->document_type,
        ]);

        return response()->json([
            'status' => 'success',
            'rule' => $rule,
        ]);
    }

    public function destroy(AuditLogService $audit, int $id): JsonResponse
    {
        $rule = ComplianceRule::find($id);
        if (!$rule) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Compliance rule not found.',
            ], 404);
        }

        $documentType = $rule->document_type;
        $rule->delete();

        $audit->record($this->authUser(), 'compliance_rule.delete', 'compliance_rule', $id, [
            'document_type' => $documentType,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Compliance rule deleted.',
        ]);
    }

    private function validated(Request $request, bool $isCreate): array
    {
        $rules = [
            'document_type' => ['sometimes', 'string', 'max:255'],
            'sector_applicability' => ['nullable'],
            'required_keywords' => ['nullable'],
            'max_age_months' => ['nullable', 'integer', 'min:1'],
            'constraints' => ['nullable'],
        ];
        if ($isCreate) {
            $rules['document_type'] = ['required', 'string', 'max:255'];
        }

        $data = $request->validate($rules);

        $data['sector_applicability'] = $this->normalizeStringArray($this->parseArray($request->input('sector_applicability')));
        $data['required_keywords'] = $this->normalizeStringArray($this->parseArray($request->input('required_keywords')));
        $data['constraints'] = $this->normalizeConstraints($this->parseJson($request->input('constraints')));

        return array_filter($data, fn ($value) => $value !== null);
    }

    private function parseArray($value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return null;
            }
            if (str_starts_with($trimmed, '[')) {
                $decoded = json_decode($trimmed, true);
                return is_array($decoded) ? $decoded : null;
            }
            $parts = array_filter(array_map('trim', explode(',', $trimmed)));
            return $parts ?: null;
        }

        return null;
    }

    private function parseJson($value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value)) {
            return null;
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function normalizeStringArray(?array $values): ?array
    {
        if (!$values) {
            return null;
        }
        $result = [];
        foreach ($values as $item) {
            $value = trim((string) $item);
            if ($value === '') {
                continue;
            }
            if (!in_array($value, $result, true)) {
                $result[] = $value;
            }
        }
        return $result ?: null;
    }

    private function normalizeConstraints(?array $constraints): ?array
    {
        if (!$constraints) {
            return null;
        }

        $allowed = ['expiry_required', 'required_fields', 'required_document', 'company_type_keys', 'required_levels'];
        $normalized = [];

        if (array_key_exists('expiry_required', $constraints)) {
            $val = $constraints['expiry_required'];
            if ($val !== null && !is_bool($val)) {
                throw ValidationException::withMessages([
                    'constraints.expiry_required' => 'expiry_required must be boolean or null.',
                ]);
            }
            $normalized['expiry_required'] = $val;
        }

        if (array_key_exists('required_document', $constraints)) {
            $val = $constraints['required_document'];
            if (!is_bool($val)) {
                throw ValidationException::withMessages([
                    'constraints.required_document' => 'required_document must be boolean.',
                ]);
            }
            $normalized['required_document'] = $val;
        }

        if (array_key_exists('required_fields', $constraints)) {
            if (!is_array($constraints['required_fields'])) {
                throw ValidationException::withMessages([
                    'constraints.required_fields' => 'required_fields must be an array.',
                ]);
            }
            $normalized['required_fields'] = $this->normalizeStringArray($constraints['required_fields']);
        }

        if (array_key_exists('company_type_keys', $constraints)) {
            if (!is_array($constraints['company_type_keys'])) {
                throw ValidationException::withMessages([
                    'constraints.company_type_keys' => 'company_type_keys must be an array.',
                ]);
            }
            $keys = $this->normalizeStringArray($constraints['company_type_keys']) ?: [];
            if ($keys && Schema::hasTable('company_type_requirements')) {
                $existing = DB::table('company_type_requirements')
                    ->whereIn('company_type_key', $keys)
                    ->pluck('company_type_key')
                    ->map(fn ($v) => (string) $v)
                    ->all();
                $missing = array_values(array_diff($keys, $existing));
                if ($missing) {
                    throw ValidationException::withMessages([
                        'constraints.company_type_keys' => 'Unknown company_type_keys: ' . implode(', ', $missing),
                    ]);
                }
            }
            $normalized['company_type_keys'] = $keys ?: null;
        }

        if (array_key_exists('required_levels', $constraints)) {
            if (!is_array($constraints['required_levels'])) {
                throw ValidationException::withMessages([
                    'constraints.required_levels' => 'required_levels must be an array.',
                ]);
            }
            $levels = array_map(
                fn ($value) => strtoupper(trim((string) $value)),
                $constraints['required_levels']
            );
            $levels = array_values(array_filter($levels, fn ($value) => $value !== ''));
            $levels = array_values(array_unique($levels));
            $allowedLevels = $this->availablePlanKeys();
            $invalid = array_values(array_diff($levels, $allowedLevels));
            if ($invalid) {
                throw ValidationException::withMessages([
                    'constraints.required_levels' => 'Invalid levels: ' . implode(', ', $invalid),
                ]);
            }
            $normalized['required_levels'] = $levels ?: null;
        }

        foreach ($constraints as $key => $_) {
            if (!in_array((string) $key, $allowed, true)) {
                $normalized[$key] = $constraints[$key];
            }
        }

        return $normalized ?: null;
    }

    private function authUser()
    {
        return request()->attributes->get('auth_user');
    }

    private function availablePlanKeys(): array
    {
        try {
            if (Schema::hasTable('plan_catalog')) {
                $keys = PlanCatalog::query()
                    ->where('is_active', true)
                    ->orderBy('rank')
                    ->pluck('plan_key')
                    ->map(fn ($key) => strtoupper(trim((string) $key)))
                    ->filter(fn ($key) => $key !== '')
                    ->values()
                    ->all();
                if ($keys) {
                    return $keys;
                }
            }
        } catch (\Throwable $e) {
        }

        return ['FREE', 'BUSINESS', 'ENTERPRISE', 'PRO'];
    }
}
