<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Document;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;

class AdminMetricsController extends Controller
{
    public function index(AuditLogService $audit): JsonResponse
    {
        $totalUsers = User::count();
        $totalCompanies = Company::count();
        $totalDocuments = Document::count();

        $statusCounts = Document::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $processedDocs = Document::query()
            ->whereNotNull('updated_at')
            ->where('status', '!=', 'PROCESSING')
            ->get(['created_at', 'updated_at']);

        $avgProcessingSeconds = null;
        if ($processedDocs->count() > 0) {
            $totalSeconds = $processedDocs->sum(function ($doc) {
                return $doc->updated_at->diffInSeconds($doc->created_at);
            });
            $avgProcessingSeconds = (int) round($totalSeconds / $processedDocs->count());
        }

        $audit->record($this->authUser(), 'admin.metrics.view', 'metrics', null);

        return response()->json([
            'status' => 'success',
            'metrics' => [
                'total_users' => $totalUsers,
                'total_companies' => $totalCompanies,
                'total_documents' => $totalDocuments,
                'documents_by_status' => $statusCounts,
                'avg_processing_seconds' => $avgProcessingSeconds,
            ],
        ]);
    }

    private function authUser()
    {
        return request()->attributes->get('auth_user');
    }
}
