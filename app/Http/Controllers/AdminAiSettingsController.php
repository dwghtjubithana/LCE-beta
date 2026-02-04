<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAiSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'settings' => [
                'gemini_api_key' => AppSetting::getValue('gemini_api_key'),
                'gemini_model_validation' => AppSetting::getValue('gemini_model_validation'),
                'gemini_model_summary' => AppSetting::getValue('gemini_model_summary'),
                'gemini_debug_full' => AppSetting::getValue('gemini_debug_full'),
                'gemini_temperature' => AppSetting::getValue('gemini_temperature'),
                'gemini_top_p' => AppSetting::getValue('gemini_top_p'),
                'gemini_max_output_tokens' => AppSetting::getValue('gemini_max_output_tokens'),
                'ai_require_gemini' => AppSetting::getValue('ai_require_gemini'),
                'ai_require_ocr' => AppSetting::getValue('ai_require_ocr'),
                'ai_min_ocr_confidence' => AppSetting::getValue('ai_min_ocr_confidence'),
                'ai_allow_image_only' => AppSetting::getValue('ai_allow_image_only'),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'gemini_api_key' => ['nullable', 'string'],
            'gemini_model_validation' => ['nullable', 'string', 'max:255'],
            'gemini_model_summary' => ['nullable', 'string', 'max:255'],
            'gemini_debug_full' => ['nullable', 'boolean'],
            'gemini_temperature' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'gemini_top_p' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'gemini_max_output_tokens' => ['nullable', 'integer', 'min:1', 'max:8192'],
            'ai_require_gemini' => ['nullable', 'boolean'],
            'ai_require_ocr' => ['nullable', 'boolean'],
            'ai_min_ocr_confidence' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ai_allow_image_only' => ['nullable', 'boolean'],
        ]);

        foreach ($payload as $key => $value) {
            $normalized = $value;
            if (is_bool($value)) {
                $normalized = $value ? '1' : '0';
            }
            if ($key === 'gemini_api_key' && ($normalized === null || $normalized === '')) {
                continue;
            }
            if ($key === 'gemini_api_key' && $normalized) {
                $normalized = \Illuminate\Support\Facades\Crypt::encryptString((string) $normalized);
            }
            AppSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $normalized]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'AI settings updated.',
        ]);
    }
}
