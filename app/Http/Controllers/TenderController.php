<?php

namespace App\Http\Controllers;

use App\Models\Tender;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TenderController extends Controller
{
    public function index(): JsonResponse
    {
        $tenders = Tender::query()
            ->where(function ($q) {
                $q->where('status', 'APPROVED')->orWhereNull('status');
            })
            ->orderByDesc('date')
            ->get()
            ->map(fn (Tender $tender) => $this->applyGating($tender, $this->authUser()));

        return response()->json([
            'status' => 'success',
            'tenders' => $tenders,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $tender = Tender::find($id);
        if (!$tender) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Tender not found.',
            ], 404);
        }

        if (!$this->canViewTender($tender, $this->authUser())) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Tender not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'tender' => $this->applyGating($tender, $this->authUser()),
        ]);
    }

    public function indexPublic(): JsonResponse
    {
        $guest = $this->guestUser();
        $tenders = Tender::query()
            ->where(function ($q) {
                $q->where('status', 'APPROVED')->orWhereNull('status');
            })
            ->orderByDesc('date')
            ->get()
            ->map(fn (Tender $tender) => $this->applyGating($tender, $guest));

        return response()->json([
            'status' => 'success',
            'tenders' => $tenders,
        ]);
    }

    public function showPublic(int $id): JsonResponse
    {
        $tender = Tender::query()
            ->where('id', $id)
            ->where(function ($q) {
                $q->where('status', 'APPROVED')->orWhereNull('status');
            })
            ->first();
        if (!$tender) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Tender not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'tender' => $this->applyGating($tender, $this->guestUser()),
        ]);
    }

    public function store(Request $request): JsonResponse
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
        $data['status'] = 'PENDING';
        $data['submitted_by_user_id'] = $this->authUser()->id;
        $data['submitted_at'] = now();

        $tender = Tender::create($data);

        return response()->json([
            'status' => 'success',
            'tender' => $tender,
            'message' => 'Aanbesteding ingestuurd. Deze wacht op goedkeuring.',
        ], 201);
    }

    public function listMine(): JsonResponse
    {
        $tenders = Tender::query()
            ->where('submitted_by_user_id', $this->authUser()->id)
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'status' => 'success',
            'tenders' => $tenders,
        ]);
    }

    public function downloadAttachment(int $id, int $index)
    {
        $user = $this->authUser();
        $tender = Tender::find($id);
        if (!$tender || !$this->canViewTender($tender, $user)) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Tender not found.',
            ], 404);
        }

        $isOwner = $tender->submitted_by_user_id && $tender->submitted_by_user_id === $user->id;
        $isBusiness = ($user->plan ?? 'FREE') === 'BUSINESS' && ($user->plan_status ?? 'ACTIVE') === 'ACTIVE';
        if (!$isOwner && !$isBusiness) {
            return response()->json([
                'code' => 'FORBIDDEN',
                'message' => 'Upgrade naar Business om bijlagen te bekijken.',
            ], 403);
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

    private function applyGating(Tender $tender, User $user): array
    {
        $isBusiness = ($user->plan ?? 'FREE') === 'BUSINESS' && ($user->plan_status ?? 'ACTIVE') === 'ACTIVE';
        $data = $tender->toArray();

        if (!$isBusiness) {
            $data['description'] = $data['description'] ? str_repeat('•', 20) : null;
            $data['details_url'] = null;
            $data['attachments'] = null;
        }

        return $data;
    }

    private function canViewTender(Tender $tender, User $user): bool
    {
        if (($tender->status ?? 'APPROVED') === 'APPROVED') {
            return true;
        }

        return $tender->submitted_by_user_id && $tender->submitted_by_user_id === $user->id;
    }

    private function authUser(): User
    {
        return request()->attributes->get('auth_user');
    }

    private function guestUser(): User
    {
        $user = new User();
        $user->plan = 'FREE';
        $user->plan_status = 'ACTIVE';
        return $user;
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
}
