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
        $message = 'Gemini key detected and request succeeded.';

        if (!env('GEMINI_API_KEY')) {
            $status = 'error';
            $message = 'GEMINI_API_KEY is not set.';
        } else {
            try {
                $gemini->validateDocument('Test', 'ping', '', 'application/pdf');
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
