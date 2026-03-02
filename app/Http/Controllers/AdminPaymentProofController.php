<?php

namespace App\Http\Controllers;

use App\Models\PaymentProof;
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

        $targetLevel = strtoupper((string) $request->input('target_level', 'BUSINESS'));
        if (!in_array($targetLevel, ['BUSINESS', 'ENTERPRISE'], true)) {
            return response()->json([
                'code' => 'VALIDATION_ERROR',
                'message' => 'Target level must be BUSINESS or ENTERPRISE.',
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
            $user->plan = $user->plan ?: 'FREE';
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
}
