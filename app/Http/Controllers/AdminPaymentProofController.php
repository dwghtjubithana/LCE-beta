<?php

namespace App\Http\Controllers;

use App\Models\PaymentProof;
use App\Models\PlanCatalog;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPaymentProofController extends Controller
{
    public function index(): JsonResponse
    {
        $proofs = PaymentProof::query()
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json([
            'status' => 'success',
            'payment_proofs' => $proofs,
            'target_level_options' => $this->targetLevelOptions(),
        ]);
    }

    public function approve(Request $request, AuditLogService $audit, int $id): JsonResponse
    {
        $admin = $this->authUser();
        $proof = PaymentProof::find($id);
        if (!$proof) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Payment proof not found.',
            ], 404);
        }

        $options = $this->targetLevelOptions();
        $allowedKeys = array_map(fn ($opt) => (string) ($opt['plan_key'] ?? ''), $options);
        $fallback = $allowedKeys[0] ?? 'BUSINESS';

        $targetLevel = strtoupper((string) $request->input('target_level', $fallback));
        if (!in_array($targetLevel, $allowedKeys, true)) {
            return response()->json([
                'code' => 'VALIDATION_ERROR',
                'message' => 'Target level is not allowed.',
            ], 422);
        }

        $proof->status = 'APPROVED';
        $proof->target_level = $targetLevel;
        $proof->reviewed_by = $admin->id;
        $proof->reviewed_at = now();
        $proof->save();

        $user = User::find($proof->user_id);
        if ($user) {
            $user->plan = $targetLevel;
            $user->plan_status = 'ACTIVE';
            $user->save();
        }

        $audit->record($admin, 'payment_proof.approve', 'payment_proof', $proof->id, [
            'user_id' => $proof->user_id,
            'target_level' => $targetLevel,
        ]);

        return response()->json([
            'status' => 'success',
            'payment_proof' => $proof,
        ]);
    }

    public function reject(AuditLogService $audit, int $id): JsonResponse
    {
        $admin = $this->authUser();
        $proof = PaymentProof::find($id);
        if (!$proof) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Payment proof not found.',
            ], 404);
        }

        $proof->status = 'REJECTED';
        $proof->reviewed_by = $admin->id;
        $proof->reviewed_at = now();
        $proof->save();

        $user = User::find($proof->user_id);
        if ($user) {
            $user->plan = $user->plan ?: $this->defaultPlanKey();
            $user->plan_status = 'ACTIVE';
            $user->save();
        }

        $audit->record($admin, 'payment_proof.reject', 'payment_proof', $proof->id, [
            'user_id' => $proof->user_id,
        ]);

        return response()->json([
            'status' => 'success',
            'payment_proof' => $proof,
        ]);
    }

    private function authUser(): User
    {
        return request()->attributes->get('auth_user');
    }

    private function targetLevelOptions(): array
    {
        $plans = PlanCatalog::query()
            ->where('is_active', true)
            ->where('available_for_upgrade', true)
            ->where('requires_payment_proof', true)
            ->orderBy('rank')
            ->get(['plan_key', 'plan_label', 'rank']);

        if ($plans->isEmpty()) {
            return [
                ['plan_key' => 'BUSINESS', 'plan_label' => 'Business', 'rank' => 30],
                ['plan_key' => 'ENTERPRISE', 'plan_label' => 'Enterprise', 'rank' => 40],
            ];
        }

        return $plans->map(fn ($plan) => [
            'plan_key' => strtoupper((string) $plan->plan_key),
            'plan_label' => (string) $plan->plan_label,
            'rank' => (int) $plan->rank,
        ])->values()->all();
    }

    private function defaultPlanKey(): string
    {
        $key = PlanCatalog::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->value('plan_key');
        $key = strtoupper(trim((string) $key));
        return $key !== '' ? $key : 'FREE';
    }
}
