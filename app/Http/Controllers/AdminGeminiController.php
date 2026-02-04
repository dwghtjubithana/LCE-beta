<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;

class AdminGeminiController extends Controller
{
    public function health(GeminiService $gemini, AuditLogService $audit): JsonResponse
    {
        $status = 'ok';
        $message = 'Gemini-sleutel gedetecteerd en test geslaagd.';

        if (!\App\Models\AppSetting::getValue('gemini_api_key')) {
            $status = 'error';
            $message = 'Gemini API-sleutel ontbreekt in AI-instellingen.';
        } else {
            try {
                $result = $gemini->ping();
                $resultStatus = strtoupper((string) ($result['status'] ?? ''));
                $note = (string) ($result['compliance_notitie'] ?? $result['message'] ?? '');
                if ($resultStatus !== 'OK') {
                    $status = 'error';
                    $message = $note ?: 'Gemini gaf een fout terug.';
                }
            } catch (\Throwable $e) {
                $status = 'error';
                $message = $e->getMessage();
            }
        }

        $audit->record($this->authUser(), 'admin.gemini.health', 'gemini', null, [
            'status' => $status,
        ]);

        return response()->json([
            'status' => $status === 'ok' ? 'success' : 'error',
            'result' => [
                'status' => $status,
                'message' => $message,
            ],
        ]);
    }

    private function authUser()
    {
        return request()->attributes->get('auth_user');
    }
}
