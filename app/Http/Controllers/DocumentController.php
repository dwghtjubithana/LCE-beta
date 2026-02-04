<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadDocumentRequest;
use App\Http\Requests\BulkUploadDocumentRequest;
use App\Http\Requests\ReprocessDocumentRequest;
use App\Http\Requests\ConfirmDocumentRequest;
use App\Jobs\ProcessDocument;
use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentFile;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function upload(UploadDocumentRequest $request, AuditLogService $audit): JsonResponse
    {
        $user = $this->authUser();
        if ($this->gatingEnabled() && !$this->canRunAi($user)) {
            return response()->json([
                'code' => 'PLAN_RESTRICTED',
                'message' => 'Upgrade required to run AI analysis.',
            ], 403);
        }
        if (!$this->ensureStorageReady()) {
            return response()->json([
                'code' => 'STORAGE_NOT_WRITABLE',
                'message' => 'Storage path is not writable. Please fix permissions for storage/app/uploads/secure.',
            ], 500);
        }
        $company = $this->resolveCompany($user, $request->input('company_id'));
        if (!$company) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Company not found.',
            ], 404);
        }

        $category = (string) $request->input('category_selected');
        $isIdCategory = $category === 'ID Bewijs';
        $idSubtype = (string) $request->input('id_subtype', '');
        $frontFile = $request->file('front_file');
        $backFile = $request->file('back_file');
        $singleFile = $request->file('file');
        $primaryFile = $frontFile ?: $singleFile;

        if ($isIdCategory && !in_array($idSubtype, ['paspoort', 'id_kaart', 'rijbewijs'], true)) {
            return response()->json([
                'code' => 'VALIDATION_ERROR',
                'message' => 'Kies een subtype voor ID bewijs (paspoort, id-kaart of rijbewijs).',
            ], 422);
        }

        if (!$primaryFile || !$primaryFile->isValid()) {
            return response()->json([
                'code' => 'INVALID_FILE',
                'message' => 'Uploaded file is not valid.',
            ], 422);
        }

        if ($isIdCategory && in_array($idSubtype, ['id_kaart', 'rijbewijs'], true) && (!$backFile || !$backFile->isValid())) {
            return response()->json([
                'code' => 'VALIDATION_ERROR',
                'message' => 'Voor ID-kaart en rijbewijs is een achterzijde verplicht.',
            ], 422);
        }

        $this->virusScanStub($primaryFile->getClientOriginalName());
        if ($backFile && $backFile->isValid()) {
            $this->virusScanStub($backFile->getClientOriginalName());
        }

        $ocrConfidenceFront = $request->input('ocr_confidence_front', $request->input('ocr_confidence'));
        $ocrConfidenceBack = $request->input('ocr_confidence_back');
        if ($ocrConfidenceFront !== null && (float) $ocrConfidenceFront < 40.0) {
            return response()->json([
                'code' => 'LOW_OCR_CONFIDENCE',
                'message' => 'Photo too dark or unreadable. Please upload a clearer file.',
            ], 422);
        }
        if ($ocrConfidenceBack !== null && (float) $ocrConfidenceBack < 40.0) {
            return response()->json([
                'code' => 'LOW_OCR_CONFIDENCE',
                'message' => 'Achterzijde is te donker of onleesbaar. Upload een scherpere foto.',
            ], 422);
        }

        $frontStored = $this->storeSecureFile($primaryFile);
        if (!$frontStored) {
            return response()->json([
                'code' => 'STORE_FAILED',
                'message' => 'Could not store the uploaded file. Please try again.',
            ], 500);
        }
        $backStored = null;
        if ($backFile && $backFile->isValid()) {
            $backStored = $this->storeSecureFile($backFile);
            if (!$backStored) {
                return response()->json([
                    'code' => 'STORE_FAILED',
                    'message' => 'Could not store the back side file. Please try again.',
                ], 500);
            }
        }

        $hash = $backStored
            ? hash('sha256', ($frontStored['hash'] ?? '') . '|' . ($backStored['hash'] ?? '') . '|' . $idSubtype)
            : ($frontStored['hash'] ?? null);

        $existing = Document::where('company_id', $company->id)
            ->where('file_hash_sha256', $hash)
            ->first();

        if ($existing) {
            return response()->json([
                'code' => 'DUPLICATE_DOCUMENT',
                'message' => 'This document was already uploaded for this company.',
                'document_id' => $existing->id,
                'status' => $existing->status,
            ], 409);
        }

        $document = Document::create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'category_selected' => $category,
            'status' => 'PROCESSING',
            'source_file_url' => $frontStored['path'],
            'file_hash_sha256' => $hash,
            'mime_type' => $frontStored['mime_type'],
            'original_filename' => $frontStored['original_filename'],
            'file_size' => $frontStored['file_size'],
            'ocr_confidence' => $ocrConfidenceFront,
            'extracted_data' => [
                'id_subtype' => $isIdCategory ? $idSubtype : null,
                'ocr_text' => $request->input('ocr_text_front', $request->input('ocr_text')),
                'ocr_confidence' => $ocrConfidenceFront,
                'ocr_text_front' => $request->input('ocr_text_front', $request->input('ocr_text')),
                'ocr_confidence_front' => $ocrConfidenceFront,
                'ocr_text_back' => $request->input('ocr_text_back'),
                'ocr_confidence_back' => $ocrConfidenceBack,
                'uploaded_files' => array_values(array_filter([
                    [
                        'side' => 'FRONT',
                        'path' => $frontStored['path'],
                        'filename' => $frontStored['original_filename'],
                        'mime_type' => $frontStored['mime_type'],
                        'file_size' => $frontStored['file_size'],
                    ],
                    $backStored ? [
                        'side' => 'BACK',
                        'path' => $backStored['path'],
                        'filename' => $backStored['original_filename'],
                        'mime_type' => $backStored['mime_type'],
                        'file_size' => $backStored['file_size'],
                    ] : null,
                ])),
            ],
        ]);

        if ($isIdCategory || $backStored) {
            DocumentFile::create([
                'document_id' => $document->id,
                'side' => 'FRONT',
                'file_path' => $frontStored['path'],
                'original_filename' => $frontStored['original_filename'],
                'mime_type' => $frontStored['mime_type'],
                'file_size' => $frontStored['file_size'],
                'file_hash_sha256' => $frontStored['hash'],
            ]);
            if ($backStored) {
                DocumentFile::create([
                    'document_id' => $document->id,
                    'side' => 'BACK',
                    'file_path' => $backStored['path'],
                    'original_filename' => $backStored['original_filename'],
                    'mime_type' => $backStored['mime_type'],
                    'file_size' => $backStored['file_size'],
                    'file_hash_sha256' => $backStored['hash'],
                ]);
            }
        }

        ProcessDocument::dispatch($document->id);
        $audit->record($user, 'document.upload', 'document', $document->id, [
            'company_id' => $company->id,
            'category' => $document->category_selected,
        ]);

        return response()->json([
            'status' => 'success',
            'document' => $document,
        ], 201);
    }

    public function uploadBulk(BulkUploadDocumentRequest $request, AuditLogService $audit): JsonResponse
    {
        $user = $this->authUser();
        if ($this->gatingEnabled() && !$this->canRunAi($user)) {
            return response()->json([
                'code' => 'PLAN_RESTRICTED',
                'message' => 'Upgrade required to run AI analysis.',
            ], 403);
        }
        if (!$this->ensureStorageReady()) {
            return response()->json([
                'code' => 'STORAGE_NOT_WRITABLE',
                'message' => 'Storage path is not writable. Please fix permissions for storage/app/uploads/secure.',
            ], 500);
        }

        $company = $this->resolveCompany($user, $request->input('company_id'));
        if (!$company) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Company not found.',
            ], 404);
        }

        $category = $request->input('category_selected');
        $results = [];
        foreach ($request->file('files', []) as $file) {
            if (!$file || !$file->isValid()) {
                $results[] = [
                    'filename' => $file?->getClientOriginalName(),
                    'status' => 'error',
                    'code' => 'INVALID_FILE',
                ];
                continue;
            }

            $this->virusScanStub($file->getClientOriginalName());

            $quarantinePath = $file->store('uploads/quarantine');
            $hash = hash_file('sha256', $file->getRealPath() ?: storage_path('app/' . $quarantinePath));

            $existing = Document::where('company_id', $company->id)
                ->where('file_hash_sha256', $hash)
                ->first();

            if ($existing) {
                $results[] = [
                    'filename' => $file->getClientOriginalName(),
                    'status' => 'duplicate',
                    'document_id' => $existing->id,
                    'color' => $this->statusColor($existing->status ?? 'MISSING'),
                ];
                continue;
            }

            $secureFilename = Str::uuid() . '_' . $file->getClientOriginalName();
            $securePath = 'uploads/secure/' . $secureFilename;
            Storage::disk('local')->move($quarantinePath, $securePath);
            if (!Storage::disk('local')->exists($securePath)) {
                Storage::disk('local')->putFileAs('uploads/secure', $file, $secureFilename);
            }
            if (!Storage::disk('local')->exists($securePath)) {
                $results[] = [
                    'filename' => $file->getClientOriginalName(),
                    'status' => 'error',
                    'code' => 'STORE_FAILED',
                ];
                continue;
            }

            $document = Document::create([
                'uuid' => (string) Str::uuid(),
                'company_id' => $company->id,
                'category_selected' => $category,
                'status' => 'PROCESSING',
                'source_file_url' => $securePath,
                'file_hash_sha256' => $hash,
                'mime_type' => $file->getClientMimeType(),
                'original_filename' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
            ]);

            ProcessDocument::dispatch($document->id);
            $audit->record($user, 'document.upload', 'document', $document->id, [
                'company_id' => $company->id,
                'category' => $document->category_selected,
                'bulk' => true,
            ]);

            $results[] = [
                'filename' => $file->getClientOriginalName(),
                'status' => 'queued',
                'document_id' => $document->id,
                'color' => $this->statusColor('PROCESSING'),
            ];
        }

        return response()->json([
            'status' => 'success',
            'results' => $results,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->authUser();
        $document = Document::query()
            ->where('id', $id)
            ->whereHas('company', function ($query) use ($user) {
                $query->where('owner_user_id', $user->id);
            })
            ->first();

        if (!$document) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Document not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'document' => $this->withUiStatus($document),
        ]);
    }

    public function listByCompany(int $companyId): JsonResponse
    {
        $user = $this->authUser();
        $company = Company::where('id', $companyId)
            ->where('owner_user_id', $user->id)
            ->first();

        if (!$company) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Company not found.',
            ], 404);
        }

        $documents = Document::where('company_id', $company->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn (Document $doc) => $this->withUiStatus($doc));

        return response()->json([
            'status' => 'success',
            'documents' => $documents,
        ]);
    }

    public function listMine(): JsonResponse
    {
        $user = $this->authUser();
        $company = Company::where('owner_user_id', $user->id)->first();

        if (!$company) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Company not found.',
            ], 404);
        }

        return $this->listByCompany($company->id);
    }

    public function downloadSummary(int $id)
    {
        $user = $this->authUser();
        $document = Document::query()
            ->where('id', $id)
            ->whereHas('company', function ($query) use ($user) {
                $query->where('owner_user_id', $user->id);
            })
            ->first();

        if (!$document || !$document->summary_file_path) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Summary not found.',
            ], 404);
        }

        $path = storage_path('app/' . $document->summary_file_path);
        if (!is_file($path)) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Summary file missing.',
            ], 404);
        }

        return response()->download($path);
    }

    public function reprocess(ReprocessDocumentRequest $request, AuditLogService $audit, int $id): JsonResponse
    {
        $user = $this->authUser();
        $document = Document::query()
            ->where('id', $id)
            ->whereHas('company', function ($query) use ($user) {
                $query->where('owner_user_id', $user->id);
            })
            ->first();

        if (!$document) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Document not found.',
            ], 404);
        }

        if ($this->gatingEnabled() && !$this->canRunAi($user)) {
            return response()->json([
                'code' => 'PLAN_RESTRICTED',
                'message' => 'Upgrade required to run AI analysis.',
            ], 403);
        }

        $document->status = 'PROCESSING';
        $document->save();

        ProcessDocument::dispatch($document->id);
        $audit->record($user, 'document.reprocess', 'document', $document->id, [
            'company_id' => $document->company_id,
        ]);

        return response()->json([
            'status' => 'success',
            'document' => $this->withUiStatus($document),
        ]);
    }

    public function confirm(ConfirmDocumentRequest $request, AuditLogService $audit, int $id): JsonResponse
    {
        $user = $this->authUser();
        $document = Document::query()
            ->where('id', $id)
            ->whereHas('company', function ($query) use ($user) {
                $query->where('owner_user_id', $user->id);
            })
            ->first();

        if (!$document) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Document not found.',
            ], 404);
        }

        $document->category_selected = $request->input('category_selected');
        $document->status = 'PROCESSING';
        $document->save();

        ProcessDocument::dispatch($document->id);
        $audit->record($user, 'document.confirm', 'document', $document->id, [
            'company_id' => $document->company_id,
            'category_selected' => $document->category_selected,
        ]);

        return response()->json([
            'status' => 'success',
            'document' => $this->withUiStatus($document),
        ]);
    }

    public function destroy(AuditLogService $audit, int $id): JsonResponse
    {
        $user = $this->authUser();
        $document = Document::query()
            ->where('id', $id)
            ->whereHas('company', function ($query) use ($user) {
                $query->where('owner_user_id', $user->id);
            })
            ->first();

        if (!$document) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Document not found.',
            ], 404);
        }

        if ($document->source_file_url) {
            Storage::disk('local')->delete($document->source_file_url);
        }
        foreach ($document->files as $file) {
            if ($file->file_path && $file->file_path !== $document->source_file_url) {
                Storage::disk('local')->delete($file->file_path);
            }
        }
        if ($document->summary_file_path) {
            Storage::disk('local')->delete($document->summary_file_path);
        }

        $documentId = $document->id;
        $companyId = $document->company_id;
        $document->files()->delete();
        $document->delete();

        $audit->record($user, 'document.delete', 'document', $documentId, [
            'company_id' => $companyId,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Document deleted.',
        ]);
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

    private function canRunAi(User $user): bool
    {
        $plan = $user->plan ?? 'FREE';
        $status = $user->plan_status ?? 'ACTIVE';
        return $status === 'ACTIVE' && in_array($plan, ['PRO', 'BUSINESS'], true);
    }

    private function gatingEnabled(): bool
    {
        return (bool) env('FEATURE_GATING_ENABLED', false);
    }

    private function withUiStatus(Document $document): array
    {
        $mapping = [
            'MISSING' => ['label' => 'Missing', 'action' => 'Uploaden'],
            'PROCESSING' => ['label' => 'Processing', 'action' => 'Bekijk'],
            'VALID' => ['label' => 'Valid', 'action' => 'Bekijk'],
            'INVALID' => ['label' => 'Invalid', 'action' => 'Fix met AI'],
            'EXPIRED' => ['label' => 'Expired', 'action' => 'Vernieuw'],
            'EXPIRING_SOON' => ['label' => 'Expiring Soon', 'action' => 'Vernieuw'],
            'MANUAL_REVIEW' => ['label' => 'Manual Review', 'action' => 'Bekijk'],
            'NEEDS_CONFIRMATION' => ['label' => 'Needs Confirmation', 'action' => 'Bevestig'],
        ];
        $ui = $mapping[$document->status] ?? ['label' => 'Unknown', 'action' => 'Bekijk'];
        $color = $this->statusColor($document->status);

        return array_merge($document->toArray(), [
            'ui_label' => $ui['label'],
            'recommended_action' => $ui['action'],
            'color' => $color,
        ]);
    }

    private function statusColor(string $status): string
    {
        $colors = [
            'MISSING' => 'Grijs',
            'PROCESSING' => 'Blauw',
            'VALID' => 'Groen',
            'INVALID' => 'Rood',
            'EXPIRED' => 'Rood',
            'EXPIRING_SOON' => 'Oranje',
            'MANUAL_REVIEW' => 'Oranje',
            'NEEDS_CONFIRMATION' => 'Oranje',
        ];

        return $colors[$status] ?? 'Grijs';
    }

    private function virusScanStub(string $filename): void
    {
        Log::info('Virus scan skipped (stub).', ['filename' => $filename]);
    }

    private function ensureStorageReady(): bool
    {
        $disk = Storage::disk('local');
        $path = $disk->path('uploads/secure');
        if (!is_dir($path)) {
            @mkdir($path, 0775, true);
        }
        return is_dir($path) && is_writable($path);
    }

    private function storeSecureFile(UploadedFile $file): ?array
    {
        $quarantinePath = $file->store('uploads/quarantine');
        $hash = hash_file('sha256', $file->getRealPath() ?: storage_path('app/' . $quarantinePath));

        $secureFilename = Str::uuid() . '_' . $file->getClientOriginalName();
        $securePath = 'uploads/secure/' . $secureFilename;
        Storage::disk('local')->move($quarantinePath, $securePath);
        if (!Storage::disk('local')->exists($securePath)) {
            Storage::disk('local')->putFileAs('uploads/secure', $file, $secureFilename);
        }
        if (!Storage::disk('local')->exists($securePath)) {
            return null;
        }

        return [
            'path' => $securePath,
            'hash' => $hash,
            'mime_type' => $file->getClientMimeType(),
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
        ];
    }
}
