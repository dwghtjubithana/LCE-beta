<?php

namespace App\Http\Controllers;

use App\Models\ComplianceRule;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $data['sector_applicability'] = $this->parseArray($request->input('sector_applicability'));
        $data['required_keywords'] = $this->parseArray($request->input('required_keywords'));
        $data['constraints'] = $this->parseJson($request->input('constraints'));

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

    private function authUser()
    {
        return request()->attributes->get('auth_user');
    }
}
