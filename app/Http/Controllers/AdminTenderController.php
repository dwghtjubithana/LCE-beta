<?php

namespace App\Http\Controllers;

use App\Models\Tender;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminTenderController extends Controller
{
    public function index(Request $request, AuditLogService $audit): JsonResponse
    {
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 ? min($limit, 100) : 20;
        $page = (int) $request->query('page', 1);
        $page = $page > 0 ? $page : 1;
        $search = trim((string) $request->query('search', ''));
        $status = strtoupper(trim((string) $request->query('status', '')));

        $query = Tender::query()->orderByDesc('date');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('client', 'like', "%{$search}%");
            });
        }
        if (in_array($status, ['PENDING', 'APPROVED', 'REJECTED'], true)) {
            $query->where('status', $status);
        }

        $total = (clone $query)->count();
        $tenders = $query->forPage($page, $limit)->get();

        $audit->record($this->authUser(), 'admin.tenders.view', 'tender', null, [
            'limit' => $limit,
            'page' => $page,
            'search' => $search ?: null,
        ]);

        return response()->json([
            'status' => 'success',
            'tenders' => $tenders,
            'meta' => [
                'page' => $page,
                'per_page' => $limit,
                'total' => $total,
                'total_pages' => $limit > 0 ? (int) ceil($total / $limit) : 1,
            ],
        ]);
    }

    public function show(int $id, AuditLogService $audit): JsonResponse
    {
        $tender = Tender::find($id);
        if (!$tender) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Tender not found.',
            ], 404);
        }

        $audit->record($this->authUser(), 'admin.tenders.show', 'tender', $tender->id);

        return response()->json([
            'status' => 'success',
            'tender' => $tender,
        ]);
    }

    public function store(Request $request, AuditLogService $audit): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'project' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'client' => ['nullable', 'string', 'max:255'],
            'details_url' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable'],
            'attachments_urls' => ['nullable', 'string'],
            'attachments_files.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:10240'],
            'description' => ['nullable', 'string'],
            'is_direct_work' => ['nullable', 'boolean'],
        ]);

        if (empty($data['project'])) {
            $data['project'] = $data['title'];
        }

        $data['attachments'] = $this->buildAttachments(
            $request->input('attachments'),
            $request->input('attachments_urls'),
            $request->file('attachments_files', [])
        );
        $data['is_direct_work'] = (bool) ($data['is_direct_work'] ?? false);
        $data['status'] = 'APPROVED';
        $data['approved_by_user_id'] = $this->authUser()?->id;
        $data['approved_at'] = now();

        $tender = Tender::create($data);

        $audit->record($this->authUser(), 'tender.create', 'tender', $tender->id, [
            'title' => $tender->title,
        ]);

        return response()->json([
            'status' => 'success',
            'tender' => $tender,
        ], 201);
    }

    public function update(Request $request, AuditLogService $audit, int $id): JsonResponse
    {
        $tender = Tender::find($id);
        if (!$tender) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Tender not found.',
            ], 404);
        }

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'project' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'client' => ['nullable', 'string', 'max:255'],
            'details_url' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable'],
            'attachments_urls' => ['nullable', 'string'],
            'attachments_files.*' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:10240'],
            'description' => ['nullable', 'string'],
            'is_direct_work' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string'],
        ]);

        if (!$data && !$request->has('attachments')) {
            return response()->json([
                'code' => 'VALIDATION_ERROR',
                'message' => 'Provide at least one field to update.',
            ], 422);
        }

        if ($request->has('attachments') || $request->has('attachments_urls') || $request->hasFile('attachments_files')) {
            $data['attachments'] = $this->buildAttachments(
                $request->input('attachments'),
                $request->input('attachments_urls'),
                $request->file('attachments_files', [])
            );
        }
        if (array_key_exists('is_direct_work', $data)) {
            $data['is_direct_work'] = (bool) $data['is_direct_work'];
        }
        if (array_key_exists('status', $data)) {
            $status = strtoupper((string) $data['status']);
            if (in_array($status, ['PENDING', 'APPROVED', 'REJECTED'], true)) {
                $data['status'] = $status;
            } else {
                unset($data['status']);
            }
        }
        if (array_key_exists('project', $data) && ($data['project'] === null || $data['project'] === '')) {
            unset($data['project']);
        }

        $tender->fill($data);
        $tender->save();

        $audit->record($this->authUser(), 'tender.update', 'tender', $tender->id, [
            'title' => $tender->title,
        ]);

        return response()->json([
            'status' => 'success',
            'tender' => $tender,
        ]);
    }

    public function approve(AuditLogService $audit, int $id): JsonResponse
    {
        $tender = Tender::find($id);
        if (!$tender) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Tender not found.',
            ], 404);
        }

        $tender->status = 'APPROVED';
        $tender->approved_by_user_id = $this->authUser()?->id;
        $tender->approved_at = now();
        $tender->save();

        $audit->record($this->authUser(), 'tender.approve', 'tender', $tender->id, [
            'title' => $tender->title,
        ]);

        return response()->json([
            'status' => 'success',
            'tender' => $tender,
        ]);
    }

    public function reject(AuditLogService $audit, int $id): JsonResponse
    {
        $tender = Tender::find($id);
        if (!$tender) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Tender not found.',
            ], 404);
        }

        $tender->status = 'REJECTED';
        $tender->approved_by_user_id = $this->authUser()?->id;
        $tender->approved_at = now();
        $tender->save();

        $audit->record($this->authUser(), 'tender.reject', 'tender', $tender->id, [
            'title' => $tender->title,
        ]);

        return response()->json([
            'status' => 'success',
            'tender' => $tender,
        ]);
    }

    public function downloadAttachment(int $id, int $index)
    {
        $tender = Tender::find($id);
        if (!$tender) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Tender not found.',
            ], 404);
        }

        $attachments = $this->normalizeAttachmentItems($tender->attachments);
        if (!isset($attachments[$index])) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Attachment not found.',
            ], 404);
        }

        $attachment = $attachments[$index];
        if (($attachment['type'] ?? '') === 'url' && !empty($attachment['url'])) {
            return response()->json([
                'status' => 'success',
                'attachment' => $attachment,
            ]);
        }

        if (($attachment['type'] ?? '') !== 'file' || empty($attachment['path'])) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Attachment not found.',
            ], 404);
        }

        $disk = Storage::disk('local');
        if (!$disk->exists($attachment['path'])) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Attachment file missing.',
            ], 404);
        }

        $downloadName = $attachment['name'] ?? basename($attachment['path']);
        return response()->file($disk->path($attachment['path']), [
            'Content-Disposition' => 'inline; filename="' . $downloadName . '"',
        ]);
    }

    public function destroy(AuditLogService $audit, int $id): JsonResponse
    {
        $tender = Tender::find($id);
        if (!$tender) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Tender not found.',
            ], 404);
        }

        $title = $tender->title;
        $tender->delete();

        $audit->record($this->authUser(), 'tender.delete', 'tender', $id, [
            'title' => $title,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Tender deleted.',
        ]);
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

    private function buildAttachments($legacyAttachments, ?string $urlsText, array $files): ?array
    {
        $attachments = $this->normalizeAttachmentItems($legacyAttachments);
        $attachments = array_merge($attachments, $this->parseUrlAttachments($urlsText));
        $attachments = array_merge($attachments, $this->storeFileAttachments($files));
        return $attachments ?: null;
    }

    private function parseUrlAttachments(?string $urlsText): array
    {
        if (!$urlsText) {
            return [];
        }
        $urls = preg_split('/\r\n|\r|\n/', $urlsText) ?: [];
        $items = [];
        foreach ($urls as $url) {
            $url = trim((string) $url);
            if ($url === '') {
                continue;
            }
            $items[] = ['type' => 'url', 'url' => $url];
        }
        return $items;
    }

    private function storeFileAttachments(array $files): array
    {
        $items = [];
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile || !$file->isValid()) {
                continue;
            }
            $stored = $file->storeAs('uploads/tenders', Str::uuid() . '_' . $file->getClientOriginalName(), 'local');
            if (!$stored) {
                continue;
            }
            $items[] = [
                'type' => 'file',
                'path' => $stored,
                'name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
            ];
        }
        return $items;
    }

    private function normalizeAttachmentItems($attachments): array
    {
        if (!is_array($attachments)) {
            return [];
        }
        $normalized = [];
        foreach ($attachments as $item) {
            if (is_string($item) && trim($item) !== '') {
                $normalized[] = ['type' => 'url', 'url' => trim($item)];
                continue;
            }
            if (!is_array($item)) {
                continue;
            }
            $type = strtolower((string) ($item['type'] ?? ''));
            if ($type === 'file' && !empty($item['path'])) {
                $normalized[] = [
                    'type' => 'file',
                    'path' => $item['path'],
                    'name' => $item['name'] ?? basename((string) $item['path']),
                    'mime_type' => $item['mime_type'] ?? null,
                    'file_size' => $item['file_size'] ?? null,
                ];
            } elseif (($type === 'url' || !isset($item['type'])) && !empty($item['url'])) {
                $normalized[] = ['type' => 'url', 'url' => $item['url']];
            }
        }
        return $normalized;
    }

    private function authUser()
    {
        return request()->attributes->get('auth_user');
    }
}
