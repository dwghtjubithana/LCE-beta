<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminDocumentController extends Controller
{
    public function index(Request $request, AuditLogService $audit): JsonResponse
    {
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 ? min($limit, 100) : 20;
        $page = (int) $request->query('page', 1);
        $page = $page > 0 ? $page : 1;
        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $category = trim((string) $request->query('category', ''));

        $query = Document::query()->orderByDesc('created_at');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('original_filename', 'like', "%{$search}%")
                    ->orWhere('category_selected', 'like', "%{$search}%")
                    ->orWhere('detected_type', 'like', "%{$search}%");
            });
        }
        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($category !== '') {
            $query->where('category_selected', $category);
        }

        $total = (clone $query)->count();
        $documents = $query->forPage($page, $limit)->get();

        $audit->record($this->authUser(), 'admin.documents.view', 'document', null, [
            'limit' => $limit,
            'page' => $page,
            'search' => $search ?: null,
            'status' => $status ?: null,
            'category' => $category ?: null,
        ]);

        return response()->json([
            'status' => 'success',
            'documents' => $documents,
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
        $document = Document::with('files')->find($id);
        if (!$document) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Document not found.',
            ], 404);
        }

        $audit->record($this->authUser(), 'admin.documents.view_one', 'document', $document->id);

        return response()->json([
            'status' => 'success',
            'document' => $document,
        ]);
    }

    public function approve(Request $request, AuditLogService $audit, int $id): JsonResponse
    {
        $document = Document::find($id);
        if (!$document) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Document not found.',
            ], 404);
        }

        $note = trim((string) $request->input('note', ''));
        $document->status = 'VALID';
        $document->ai_feedback = $this->appendAdminNote($document->ai_feedback, 'Handmatig goedgekeurd door admin.', $note);
        $document->save();

        $audit->record($this->authUser(), 'admin.documents.approve', 'document', $document->id, [
            'note' => $note ?: null,
        ]);

        return response()->json([
            'status' => 'success',
            'document' => $document,
        ]);
    }

    public function reject(Request $request, AuditLogService $audit, int $id): JsonResponse
    {
        $document = Document::find($id);
        if (!$document) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Document not found.',
            ], 404);
        }

        $note = trim((string) $request->input('note', ''));
        $document->status = 'INVALID';
        $document->ai_feedback = $this->appendAdminNote($document->ai_feedback, 'Handmatig afgewezen door admin.', $note);
        $document->save();

        $audit->record($this->authUser(), 'admin.documents.reject', 'document', $document->id, [
            'note' => $note ?: null,
        ]);

        return response()->json([
            'status' => 'success',
            'document' => $document,
        ]);
    }

    public function downloadFile(int $id, string $side = 'FRONT')
    {
        $document = Document::with('files')->find($id);
        if (!$document) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Document not found.',
            ], 404);
        }

        $side = strtoupper(trim($side));
        $filePath = null;
        $downloadName = $document->original_filename ?: 'document';

        $match = $document->files->firstWhere('side', $side);
        if ($match) {
            $filePath = $match->file_path;
            $downloadName = $match->original_filename ?: $downloadName;
        } elseif ($side === 'FRONT') {
            $filePath = $document->source_file_url;
        }

        if (!$filePath) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Document file not found.',
            ], 404);
        }

        $disk = Storage::disk('local');
        if (!$disk->exists($filePath)) {
            return response()->json([
                'code' => 'NOT_FOUND',
                'message' => 'Document file missing.',
            ], 404);
        }

        return response()->file($disk->path($filePath), [
            'Content-Disposition' => 'inline; filename="' . $downloadName . '"',
        ]);
    }

    private function authUser()
    {
        return request()->attributes->get('auth_user');
    }

    private function appendAdminNote(?string $existing, string $prefix, ?string $note): string
    {
        $base = trim((string) $existing);
        $suffix = $note ? " Opmerking: {$note}" : '';
        $line = $prefix . $suffix;
        if ($base === '') {
            return $line;
        }
        return $base . ' ' . $line;
    }
}
