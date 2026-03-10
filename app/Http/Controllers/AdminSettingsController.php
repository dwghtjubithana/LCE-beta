<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class AdminSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'settings' => [
                'ai_validation_retry_count' => $this->toInt(AppSetting::getValue('ai_validation_retry_count', 1), 1),
                'gemini_timeout_seconds' => $this->toInt(AppSetting::getValue('gemini_timeout_seconds', 60), 60),
                'ai_include_internal_debug_paths' => $this->toBool(AppSetting::getValue('ai_include_internal_debug_paths', '0')),
                'ai_expose_debug_meta_to_user' => $this->toBool(AppSetting::getValue('ai_expose_debug_meta_to_user', '0')),
                'upload_malware_scan_mode' => $this->toScanMode(AppSetting::getValue('upload_malware_scan_mode', 'OFF')),
                'upload_malware_scan_timeout_seconds' => $this->toInt(AppSetting::getValue('upload_malware_scan_timeout_seconds', 20), 20),
                'upload_malware_scan_binary' => (string) AppSetting::getValue('upload_malware_scan_binary', 'clamscan'),
                'upload_malware_scan_block_on_error' => $this->toBool(AppSetting::getValue('upload_malware_scan_block_on_error', '0')),
                'upload_malware_scan_last_error_at' => AppSetting::getValue('upload_malware_scan_last_error_at'),
                'upload_malware_scan_last_error' => AppSetting::getValue('upload_malware_scan_last_error'),
            ],
        ]);
    }

    public function update(Request $request, AuditLogService $audit): JsonResponse
    {
        $payload = $request->validate([
            'ai_validation_retry_count' => ['nullable', 'integer', 'min:0', 'max:3'],
            'gemini_timeout_seconds' => ['nullable', 'integer', 'min:5', 'max:180'],
            'ai_include_internal_debug_paths' => ['nullable', 'boolean'],
            'ai_expose_debug_meta_to_user' => ['nullable', 'boolean'],
            'upload_malware_scan_mode' => ['nullable', 'in:OFF,WARN,ENFORCE'],
            'upload_malware_scan_timeout_seconds' => ['nullable', 'integer', 'min:5', 'max:120'],
            'upload_malware_scan_binary' => ['nullable', 'string', 'max:120'],
            'upload_malware_scan_block_on_error' => ['nullable', 'boolean'],
        ]);

        if (array_key_exists('upload_malware_scan_binary', $payload)) {
            $payload['upload_malware_scan_binary'] = trim((string) $payload['upload_malware_scan_binary']) ?: 'clamscan';
        }

        foreach ($payload as $key => $value) {
            AppSetting::setValue($key, $value);
        }

        $audit->record($this->authUser(), 'admin.settings.update', 'settings', null, [
            'settings_keys' => array_keys($payload),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Global settings updated.',
        ]);
    }

    public function scannerHealth(AuditLogService $audit): JsonResponse
    {
        $binary = trim((string) AppSetting::getValue('upload_malware_scan_binary', 'clamscan')) ?: 'clamscan';
        $mode = $this->toScanMode(AppSetting::getValue('upload_malware_scan_mode', 'OFF'));
        $timeout = $this->toInt(AppSetting::getValue('upload_malware_scan_timeout_seconds', 20), 20);
        $timeout = max(3, min(30, $timeout));

        $available = false;
        $version = null;
        $error = null;
        $exitCode = null;

        $process = new Process([$binary, '--version']);
        $process->setTimeout($timeout);

        try {
            $process->run();
            $exitCode = $process->getExitCode();
            $output = trim($process->getOutput() . "\n" . $process->getErrorOutput());
            if ($exitCode === 0) {
                $available = true;
                $version = strtok($output, "\n") ?: 'Scanner responded without version output.';
            } else {
                $error = 'Scanner command returned exit code ' . (string) $exitCode . '.';
            }
        } catch (ProcessTimedOutException $e) {
            $error = 'Scanner health check timed out.';
        } catch (\Throwable $e) {
            $error = 'Scanner binary not executable or not found.';
        }

        if ($error !== null) {
            AppSetting::setValue('upload_malware_scan_last_error_at', now()->toDateTimeString());
            AppSetting::setValue('upload_malware_scan_last_error', $error);
        }

        $audit->record($this->authUser(), 'admin.settings.scanner_health', 'settings', null, [
            'scanner_binary' => $binary,
            'scanner_mode' => $mode,
            'available' => $available,
            'exit_code' => $exitCode,
        ]);

        return response()->json([
            'status' => 'success',
            'health' => [
                'available' => $available,
                'binary' => $binary,
                'mode' => $mode,
                'timeout_seconds' => $timeout,
                'version' => $version,
                'error' => $error,
                'last_error_at' => AppSetting::getValue('upload_malware_scan_last_error_at'),
                'last_error' => AppSetting::getValue('upload_malware_scan_last_error'),
                'checked_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function authUser()
    {
        return request()->attributes->get('auth_user');
    }

    private function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        return in_array((string) $value, ['1', 'true', 'TRUE', 'yes', 'on'], true);
    }

    private function toInt($value, int $default): int
    {
        if ($value === null || $value === '') {
            return $default;
        }
        return (int) $value;
    }

    private function toScanMode($value): string
    {
        $normalized = strtoupper(trim((string) $value));
        if (in_array($normalized, ['OFF', 'WARN', 'ENFORCE'], true)) {
            return $normalized;
        }
        return 'OFF';
    }
}
