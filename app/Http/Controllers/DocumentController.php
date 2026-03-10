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
use App\Services\DocumentRequirementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class DocumentController extends Controller
{
    public function upload(UploadDocumentRequest $request, AuditLogService $audit, DocumentRequirementService $requirements): JsonResponse
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
        $blockedResponse = $this->validateCategoryAllowed($company, $category, $requirements, $this->normalizedPlan($user));
        if ($blockedResponse) {
            return $blockedResponse;
        }
        $normalizedCategory = $this->normalizeCategory($category);
        $isBaselineCategory = in_array($normalizedCategory, $this->normalizedBaselineTypes(), true);
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
        if (!($frontStored['ok'] ?? false)) {
            return response()->json([
                'code' => $frontStored['code'] ?? 'STORE_FAILED',
                'message' => $frontStored['message'] ?? 'Could not store the uploaded file. Please try again.',
            ], ($frontStored['http_status'] ?? 500));
        }
        $backStored = null;
        if ($backFile && $backFile->isValid()) {
            $backStored = $this->storeSecureFile($backFile);
            if (!($backStored['ok'] ?? false)) {
                if (!empty($frontStored['path'])) {
                    Storage::disk('local')->delete($frontStored['path']);
                }
                return response()->json([
                    'code' => $backStored['code'] ?? 'STORE_FAILED',
                    'message' => $backStored['message'] ?? 'Could not store the back side file. Please try again.',
                ], ($backStored['http_status'] ?? 500));
            }
        }

        $hash = $backStored
            ? hash('sha256', ($frontStored['hash'] ?? '') . '|' . ($backStored['hash'] ?? '') . '|' . $idSubtype)
            : ($frontStored['hash'] ?? null);

        $existing = Document::where('company_id', $company->id)
            ->where('file_hash_sha256', $hash)
            ->first();

        if ($existing) {
            if (!empty($frontStored['path'])) {
                Storage::disk('local')->delete($frontStored['path']);
            }
            if (!empty($backStored['path'])) {
                Storage::disk('local')->delete($backStored['path']);
            }
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
            'is_baseline' => $isBaselineCategory,
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
                'upload_scan' => array_values(array_filter([
                    $frontStored['scan'] ?? null,
                    $backStored['scan'] ?? null,
                ])),
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
            'document' => $this->withUiStatus($document),
        ], 201);
    }

    public function uploadBulk(BulkUploadDocumentRequest $request, AuditLogService $audit, DocumentRequirementService $requirements): JsonResponse
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
        $blockedResponse = $this->validateCategoryAllowed($company, (string) $category, $requirements, $this->normalizedPlan($user));
        if ($blockedResponse) {
            return $blockedResponse;
        }
        $normalizedCategory = $this->normalizeCategory((string) $category);
        $isBaselineCategory = in_array($normalizedCategory, $this->normalizedBaselineTypes(), true);
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

            $stored = $this->storeSecureFile($file);
            if (!($stored['ok'] ?? false)) {
                $results[] = [
                    'filename' => $file->getClientOriginalName(),
                    'status' => 'error',
                    'code' => $stored['code'] ?? 'STORE_FAILED',
                    'message' => $stored['message'] ?? 'Could not store uploaded file.',
                ];
                continue;
            }

            $hash = (string) ($stored['hash'] ?? '');

            $existing = Document::where('company_id', $company->id)
                ->where('file_hash_sha256', $hash)
                ->first();

            if ($existing) {
                if (!empty($stored['path'])) {
                    Storage::disk('local')->delete($stored['path']);
                }
                $results[] = [
                    'filename' => $file->getClientOriginalName(),
                    'status' => 'duplicate',
                    'document_id' => $existing->id,
                    'color' => $this->statusColor($existing->status ?? 'MISSING'),
                ];
                continue;
            }

            $document = Document::create([
                'uuid' => (string) Str::uuid(),
                'company_id' => $company->id,
                'category_selected' => $category,
                'is_baseline' => $isBaselineCategory,
                'status' => 'PROCESSING',
                'source_file_url' => $stored['path'],
                'file_hash_sha256' => $hash,
                'mime_type' => $stored['mime_type'] ?? $file->getClientMimeType(),
                'original_filename' => $stored['original_filename'] ?? $file->getClientOriginalName(),
                'file_size' => $stored['file_size'] ?? $file->getSize(),
                'extracted_data' => array_values(array_filter([
                    $stored['scan'] ?? null,
                ])) ? ['upload_scan' => [$stored['scan']]] : null,
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

    public function confirm(ConfirmDocumentRequest $request, AuditLogService $audit, DocumentRequirementService $requirements, int $id): JsonResponse
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

        $category = (string) $request->input('category_selected');
        $blockedResponse = $this->validateCategoryAllowed($document->company, $category, $requirements, $this->normalizedPlan($user));
        if ($blockedResponse) {
            return $blockedResponse;
        }
        $normalizedCategory = $this->normalizeCategory($category);

        $document->category_selected = $category;
        $document->is_baseline = in_array($normalizedCategory, $this->normalizedBaselineTypes(), true);
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
        $status = $user->plan_status ?? 'ACTIVE';
        return strtoupper((string) $status) === 'ACTIVE';
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

        $payload = $document->toArray();
        if (!$this->settingBool('ai_expose_debug_meta_to_user', false)) {
            $extracted = $payload['extracted_data'] ?? null;
            if (is_array($extracted)) {
                unset($extracted['ai_debug_full'], $extracted['ai_debug_meta'], $extracted['upload_scan']);
                $payload['extracted_data'] = $extracted;
            }
        }

        return array_merge($payload, [
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

    private function ensureStorageReady(): bool
    {
        $disk = Storage::disk('local');
        $path = $disk->path('uploads/secure');
        if (!is_dir($path)) {
            @mkdir($path, 0775, true);
        }
        return is_dir($path) && is_writable($path);
    }

    private function storeSecureFile(UploadedFile $file): array
    {
        $quarantinePath = $file->store('uploads/quarantine');
        if (!$quarantinePath) {
            return [
                'ok' => false,
                'code' => 'STORE_FAILED',
                'message' => 'Could not store uploaded file in quarantine.',
                'http_status' => 500,
            ];
        }

        $disk = Storage::disk('local');
        $absoluteQuarantinePath = $disk->path($quarantinePath);
        $hash = hash_file('sha256', $absoluteQuarantinePath);
        if (!is_string($hash) || $hash === '') {
            $disk->delete($quarantinePath);
            return [
                'ok' => false,
                'code' => 'STORE_FAILED',
                'message' => 'Could not checksum uploaded file.',
                'http_status' => 500,
            ];
        }

        $scan = $this->scanUploadedFile($absoluteQuarantinePath, $file->getClientOriginalName());
        if (($scan['blocked'] ?? false) === true) {
            $disk->delete($quarantinePath);
            return [
                'ok' => false,
                'code' => $scan['code'] ?? 'MALWARE_DETECTED',
                'message' => $scan['message'] ?? 'Uploaded file failed malware scan.',
                'http_status' => $scan['http_status'] ?? 422,
                'scan' => $scan['meta'] ?? null,
            ];
        }

        $secureFilename = Str::uuid() . '_' . $file->getClientOriginalName();
        $securePath = 'uploads/secure/' . $secureFilename;
        $disk->move($quarantinePath, $securePath);
        if (!$disk->exists($securePath)) {
            $disk->putFileAs('uploads/secure', $file, $secureFilename);
        }
        if (!$disk->exists($securePath)) {
            return [
                'ok' => false,
                'code' => 'STORE_FAILED',
                'message' => 'Could not move uploaded file to secure storage.',
                'http_status' => 500,
                'scan' => $scan['meta'] ?? null,
            ];
        }

        return [
            'ok' => true,
            'path' => $securePath,
            'hash' => $hash,
            'mime_type' => $file->getClientMimeType(),
            'original_filename' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'scan' => $scan['meta'] ?? null,
        ];
    }

    private function normalizedPlan(User $user): string
    {
        $plan = strtoupper((string) ($user->plan ?? 'FREE'));
        if ($plan === 'PRO') {
            return 'BUSINESS';
        }
        return $plan;
    }

    private function normalizedBaselineTypes(): array
    {
        return array_map(fn ($type) => $this->normalizeCategory($type), Document::BASELINE_DOC_TYPES);
    }

    private function normalizeCategory(string $value): string
    {
        return strtoupper(str_replace([' ', '-'], '_', trim($value)));
    }

    private function validateCategoryAllowed(Company $company, string $category, DocumentRequirementService $requirements, string $level): ?JsonResponse
    {
        $normalizedCategory = $this->normalizeCategory($category);
        $allowed = $requirements->allowedTypesForCompany($company, $level);
        $normalizedAllowed = array_map(fn ($type) => $this->normalizeCategory((string) $type), $allowed);

        if (in_array($normalizedCategory, $normalizedAllowed, true)) {
            return null;
        }

        return response()->json([
            'code' => 'CATEGORY_NOT_ALLOWED',
            'message' => 'Dit documenttype is niet beschikbaar voor dit bedrijfstype.',
            'allowed_categories' => $allowed,
        ], 422);
    }

    private function settingBool(string $key, bool $default = false): bool
    {
        try {
            $val = \App\Models\AppSetting::getValue($key);
            if ($val === null) {
                return $default;
            }
            return in_array($val, [true, 'true', '1', 1], true);
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private function settingInt(string $key, int $default, int $min, int $max): int
    {
        try {
            $val = \App\Models\AppSetting::getValue($key);
            if ($val === null || $val === '') {
                return $default;
            }
            return max($min, min($max, (int) $val));
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private function settingEnum(string $key, array $allowed, string $default): string
    {
        try {
            $raw = strtoupper(trim((string) \App\Models\AppSetting::getValue($key, $default)));
            return in_array($raw, $allowed, true) ? $raw : $default;
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private function scanUploadedFile(string $absolutePath, string $originalFilename): array
    {
        $mode = $this->settingEnum('upload_malware_scan_mode', ['OFF', 'WARN', 'ENFORCE'], 'OFF');
        if ($mode === 'OFF') {
            return [
                'blocked' => false,
                'meta' => [
                    'filename' => $originalFilename,
                    'status' => 'SKIPPED',
                    'mode' => $mode,
                ],
            ];
        }

        $binary = trim((string) \App\Models\AppSetting::getValue('upload_malware_scan_binary', 'clamscan'));
        if ($binary === '') {
            $binary = 'clamscan';
        }
        $timeoutSeconds = $this->settingInt('upload_malware_scan_timeout_seconds', 20, 5, 120);
        $blockOnError = $this->settingBool('upload_malware_scan_block_on_error', false);

        $process = new Process([$binary, '--no-summary', $absolutePath]);
        $process->setTimeout($timeoutSeconds);

        $exitCode = null;
        $output = '';
        $error = null;
        try {
            $process->run();
            $exitCode = $process->getExitCode();
            $output = trim($process->getOutput() . "\n" . $process->getErrorOutput());
        } catch (ProcessTimedOutException $e) {
            $error = 'Virus scan timed out.';
        } catch (\Throwable $e) {
            $error = 'Virus scan command failed.';
        }

        $infected = false;
        if ($error === null) {
            $infected = ($exitCode === 1) || str_contains(strtoupper($output), 'FOUND');
        }
        $scanError = $error !== null || ($exitCode !== null && $exitCode > 1);
        $blocked = ($mode === 'ENFORCE' && $infected) || ($scanError && $blockOnError);

        $meta = [
            'filename' => $originalFilename,
            'mode' => $mode,
            'status' => $infected ? 'INFECTED' : ($scanError ? 'ERROR' : 'CLEAN'),
            'engine' => $binary,
            'exit_code' => $exitCode,
            'scanned_at' => now()->toIso8601String(),
        ];

        if ($infected) {
            Log::warning('Malware scan flagged file.', [
                'filename' => $originalFilename,
                'mode' => $mode,
            ]);
        }

        if ($scanError) {
            $snippet = $error ?: ('Scanner exit code ' . (string) $exitCode);
            \App\Models\AppSetting::setValue('upload_malware_scan_last_error_at', now()->toDateTimeString());
            \App\Models\AppSetting::setValue('upload_malware_scan_last_error', $snippet);
            Log::warning('Malware scan error.', [
                'filename' => $originalFilename,
                'mode' => $mode,
                'error' => $snippet,
            ]);
        }

        if ($blocked) {
            if ($infected) {
                return [
                    'blocked' => true,
                    'code' => 'MALWARE_DETECTED',
                    'message' => 'Upload blocked: malware detected.',
                    'http_status' => 422,
                    'meta' => $meta,
                ];
            }
            return [
                'blocked' => true,
                'code' => 'SCAN_UNAVAILABLE',
                'message' => 'Upload blocked: malware scanner unavailable.',
                'http_status' => 503,
                'meta' => $meta,
            ];
        }

        return [
            'blocked' => false,
            'meta' => $meta,
        ];
    }

}
