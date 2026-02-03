<?php

namespace App\Http\Controllers;

use App\Models\Tender;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTenderController extends Controller
{
    public function index(Request $request, AuditLogService $audit): JsonResponse
    {
        $limit = (int) $request->query('limit', 20);
        $limit = $limit > 0 ? min($limit, 100) : 20;
        $page = (int) $request->query('page', 1);
        $page = $page > 0 ? $page : 1;
        $search = trim((string) $request->query('search', ''));

        $query = Tender::query()->orderByDesc('date');
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('client', 'like', "%{$search}%");
            });
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

    public function store(Request $request, AuditLogService $audit): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'project' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'client' => ['nullable', 'string', 'max:255'],
            'details_url' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable'],
            'description' => ['nullable', 'string'],
        ]);

        if (empty($data['project'])) {
            $data['project'] = $data['title'];
        }

        $data['attachments'] = $this->parseJson($request->input('attachments'));
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
            'description' => ['nullable', 'string'],
        ]);

        if (!$data && !$request->has('attachments')) {
            return response()->json([
                'code' => 'VALIDATION_ERROR',
                'message' => 'Provide at least one field to update.',
            ], 422);
        }

        if ($request->has('attachments')) {
            $data['attachments'] = $this->parseJson($request->input('attachments'));
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

    private function authUser()
    {
        return request()->attributes->get('auth_user');
    }
}
