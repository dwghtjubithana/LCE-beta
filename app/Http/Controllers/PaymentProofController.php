<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadPaymentProofRequest;
use App\Models\Company;
use App\Models\PaymentProof;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentProofController extends Controller
{
    public function store(UploadPaymentProofRequest $request, AuditLogService $audit): JsonResponse
    {
        $user = $this->authUser();
        $company = $this->resolveCompany($user, $request->input('company_id'));
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

        $path = $file->store('uploads/payment-proofs');
        $proof = PaymentProof::create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'file_path' => $path,
            'status' => 'PENDING',
            'submitted_at' => now(),
        ]);

        $user->plan_status = 'PENDING_PAYMENT';
        $user->save();

        $audit->record($user, 'payment_proof.upload', 'payment_proof', $proof->id, [
            'company_id' => $company->id,
            'file_path' => $path,
        ]);

        return response()->json([
            'status' => 'success',
            'payment_proof' => $proof,
            'user' => [
                'plan' => $user->plan,
                'plan_status' => $user->plan_status,
            ],
        ], 201);
    }

    public function latest(): JsonResponse
    {
        $user = $this->authUser();
        $proof = PaymentProof::where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'status' => 'success',
            'payment_proof' => $proof,
        ]);
    }

    public function latestFile()
    {
        $user = $this->authUser();
        $proof = PaymentProof::where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();

        if (!$proof || !$proof->file_path) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Payment proof not found.',
            ], 404);
        }

        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        if (!$disk->exists($proof->file_path)) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Payment proof not found.',
            ], 404);
        }

        return response()->file($disk->path($proof->file_path));
    }

    private function resolveCompany(User $user, ?int $companyId): ?Company
    {
        $query = Company::where('owner_user_id', $user->id);
        if ($companyId) {
            $query->where('id', $companyId);
        }
        return $query->first();
    }

    private function authUser(): User
    {
        return request()->attributes->get('auth_user');
    }
}
