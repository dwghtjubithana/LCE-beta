<?php

namespace App\Jobs;

use App\Services\GeminiService;
use App\Services\ClassifierService;
use App\Services\OcrService;
use App\Services\RuleEngine;
use App\Services\SummaryService;
use App\Models\Document;
use App\Models\ComplianceRule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ProcessDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $documentId)
    {
    }

    public function handle(): void
    {
        $document = Document::find($this->documentId);
        if (!$document) {
            return;
        }

        $document->detected_type = $document->category_selected;

        $extractedData = $document->extracted_data ?? [];
        $summary = null;
        $summaryPath = null;

        $disk = Storage::disk('local');
        $documentFiles = $document->files()->orderBy('side')->get();
        $fileEntries = [];
        if ($documentFiles->isNotEmpty()) {
            foreach ($documentFiles as $file) {
                $fileEntries[] = [
                    'side' => strtoupper((string) $file->side),
                    'path' => $file->file_path,
                    'mime_type' => $file->mime_type ?: $document->mime_type,
                ];
            }
        } elseif ($document->source_file_url) {
            $fileEntries[] = [
                'side' => 'FRONT',
                'path' => $document->source_file_url,
                'mime_type' => $document->mime_type,
            ];
        }

        if (empty($extractedData['ocr_text'])) {
            $ocrChunks = [];
            $ocrConfidences = [];
            foreach ($fileEntries as $entry) {
                if (!$entry['path'] || !$disk->exists($entry['path'])) {
                    continue;
                }
                $ocr = app(OcrService::class)->extractText($disk->path($entry['path']));
                $text = trim((string) ($ocr['text'] ?? ''));
                if ($text !== '') {
                    $side = strtoupper((string) ($entry['side'] ?? 'FRONT'));
                    if ($side === 'BACK') {
                        $extractedData['ocr_text_back'] = $text;
                        $extractedData['ocr_confidence_back'] = $ocr['confidence'] ?? null;
                    } else {
                        $extractedData['ocr_text_front'] = $text;
                        $extractedData['ocr_confidence_front'] = $ocr['confidence'] ?? null;
                    }
                    $ocrChunks[] = "[{$side}]\n{$text}";
                }
                if (($ocr['confidence'] ?? null) !== null) {
                    $ocrConfidences[] = (float) $ocr['confidence'];
                }
            }
            if ($ocrChunks) {
                $extractedData['ocr_text'] = implode("\n\n", $ocrChunks);
            }
            if ($ocrConfidences) {
                $extractedData['ocr_confidence'] = round(array_sum($ocrConfidences) / count($ocrConfidences), 2);
            }
        }

        if (empty($extractedData['ocr_text']) && !empty($extractedData['ocr_text_front'])) {
            $chunks = ["[FRONT]\n" . $extractedData['ocr_text_front']];
            if (!empty($extractedData['ocr_text_back'])) {
                $chunks[] = "[BACK]\n" . $extractedData['ocr_text_back'];
            }
            $extractedData['ocr_text'] = implode("\n\n", $chunks);
        }

        $ocrText = trim((string) ($extractedData['ocr_text'] ?? ''));
        $ocrMissing = $ocrText === '';
        $ocrConfidence = $extractedData['ocr_confidence'] ?? null;
        $minOcrConfidence = $this->settingNumber('ai_min_ocr_confidence');
        $ocrBelowThreshold = $minOcrConfidence !== null && $ocrConfidence !== null
            ? (float) $ocrConfidence < (float) $minOcrConfidence
            : false;
        $requireOcr = $this->settingBool('ai_require_ocr', true);
        $allowImageOnly = $this->settingBool('ai_allow_image_only', true);

        $classifier = app(ClassifierService::class)->detect($extractedData['ocr_text'] ?? null);
        if ($classifier['type']) {
            $document->detected_type = $classifier['type'];
            $extractedData['classifier'] = $classifier;
        }

        $idSubtype = strtolower((string) ($extractedData['id_subtype'] ?? ''));
        $hasBackSide = !empty($extractedData['ocr_text_back']) || collect($fileEntries)->contains(fn ($entry) => strtoupper((string) ($entry['side'] ?? '')) === 'BACK');
        $requiresBack = $document->category_selected === 'ID Bewijs' && in_array($idSubtype, ['id_kaart', 'rijbewijs'], true);

        if ($this->settingBool('ai_require_gemini', true) && !$this->geminiKeyExists()) {
            $document->status = 'MANUAL_REVIEW';
            $document->ai_feedback = 'AI-analyse is momenteel niet beschikbaar. Probeer later opnieuw.';
            $document->ai_confidence = null;
            $document->extracted_data = $extractedData;
            $document->save();
            return;
        }

        $geminiCalled = false;
        $aiResult = null;
        $summary = null;
        $validationFailed = false;
        $validationError = null;
        $validationRetried = false;
        $primaryEntry = collect($fileEntries)->first(fn ($entry) => strtoupper((string) ($entry['side'] ?? '')) === 'FRONT')
            ?? ($fileEntries[0] ?? null);
        $primaryPath = $primaryEntry['path'] ?? $document->source_file_url;
        $primaryMime = $primaryEntry['mime_type'] ?? ($document->mime_type ?: 'application/pdf');
        $filePath = $primaryPath ? $disk->path($primaryPath) : null;
        $fileExists = $primaryPath ? $disk->exists($primaryPath) : false;
        if ($fileExists && ($allowImageOnly || !$ocrMissing)) {
            $geminiCalled = true;
            $contents = $disk->get($primaryPath);
            $base64 = base64_encode($contents);
            $mimeType = $primaryMime;
            $additionalFiles = [];
            foreach ($fileEntries as $entry) {
                $entryPath = $entry['path'] ?? null;
                if (!$entryPath || $entryPath === $primaryPath || !$disk->exists($entryPath)) {
                    continue;
                }
                $additionalFiles[] = [
                    'mimeType' => $entry['mime_type'] ?? 'application/octet-stream',
                    'data' => base64_encode($disk->get($entryPath)),
                ];
            }

            $ruleText = $this->buildRuleText($document->category_selected);
            $summaryInstructionText = $this->buildSummaryInstruction($document->category_selected, $ruleText);
            $instruction = [
                'file' => 'dynamic-rules',
                'version' => '1.0',
                'content' => $summaryInstructionText,
            ];
            $gemini = app(GeminiService::class);

            $aiResult = $document->category_selected === 'KKF Uittreksel'
                ? $gemini->validateKkf($base64, $mimeType, $extractedData['ocr_text'] ?? null, $additionalFiles)
                : $gemini->validateDocument(
                    $document->category_selected,
                    $ruleText,
                    $base64,
                    $mimeType,
                    $extractedData['ocr_text'] ?? null,
                    $additionalFiles
                );
            if (is_array($aiResult) && ($aiResult['status'] ?? null) === 'ERROR') {
                $validationError = (string) ($aiResult['compliance_notitie'] ?? 'Unexpected Gemini response.');
                $validationRetried = true;
                $aiResult = $document->category_selected === 'KKF Uittreksel'
                    ? $gemini->validateKkf($base64, $mimeType, $extractedData['ocr_text'] ?? null, $additionalFiles)
                    : $gemini->validateDocument(
                        $document->category_selected,
                        $ruleText,
                        $base64,
                        $mimeType,
                        $extractedData['ocr_text'] ?? null,
                        $additionalFiles
                    );
                if (is_array($aiResult) && ($aiResult['status'] ?? null) === 'ERROR') {
                    $validationFailed = true;
                    $validationError = (string) ($aiResult['compliance_notitie'] ?? $validationError);
                }
            }
            $summary = $gemini->generateComplianceSummary(
                $summaryInstructionText,
                $extractedData['ocr_text'] ?? '',
                $document->original_filename ?? 'upload',
                $mimeType,
                $base64,
                $additionalFiles
            );

            $extractedData['ai_validation'] = $aiResult;
            $extractedData['ai_summary'] = $summary;
            if ($this->geminiDebugEnabled()) {
                $extractedData['ai_debug_full'] = [
                    'validation' => $aiResult,
                    'summary' => $summary,
                ];
            }
            $extractedData['ai_debug_meta'] = [
                'timestamp' => now()->toISOString(),
                'ocr_text_len' => strlen($extractedData['ocr_text'] ?? ''),
                'ocr_confidence' => $extractedData['ocr_confidence'] ?? null,
                'allow_image_only' => $allowImageOnly,
                'require_ocr' => $requireOcr,
                'min_ocr_confidence' => $minOcrConfidence,
                'gemini_debug_enabled' => $this->geminiDebugEnabled(),
                'gemini_called' => $geminiCalled,
                'gemini_validation_status' => is_array($aiResult) ? ($aiResult['status'] ?? null) : null,
                'gemini_summary_status' => is_array($summary) ? ($summary['status'] ?? null) : null,
                'validation_retried' => $validationRetried,
                'validation_failed' => $validationFailed,
                'validation_error' => $validationError,
                'id_subtype' => $idSubtype ?: null,
                'has_back_side' => $hasBackSide,
                'requires_back_side' => $requiresBack,
            ];
            $extractedData['instruction'] = [
                'file' => $instruction['file'],
                'version' => $instruction['version'],
            ];
            if (is_array($aiResult) && isset($aiResult['extracted_data']) && is_array($aiResult['extracted_data'])) {
                $extractedData = array_merge($extractedData, $aiResult['extracted_data']);
            }

            if (is_array($summary)) {
                $summaryPath = app(SummaryService::class)->generate($document, $summary, $instruction);
            }
        }
        if (!isset($extractedData['ai_debug_meta'])) {
            $extractedData['ai_debug_meta'] = [
                'timestamp' => now()->toISOString(),
                'ocr_text_len' => strlen($extractedData['ocr_text'] ?? ''),
                'ocr_confidence' => $extractedData['ocr_confidence'] ?? null,
                'allow_image_only' => $allowImageOnly,
                'require_ocr' => $requireOcr,
                'min_ocr_confidence' => $minOcrConfidence,
                'gemini_debug_enabled' => $this->geminiDebugEnabled(),
                'gemini_called' => $geminiCalled,
                'gemini_validation_status' => is_array($aiResult) ? ($aiResult['status'] ?? null) : null,
                'gemini_summary_status' => is_array($summary) ? ($summary['status'] ?? null) : null,
                'file_exists' => $fileExists,
                'file_path' => $filePath,
                'storage_root' => $disk->path(''),
                'validation_retried' => $validationRetried,
                'validation_failed' => $validationFailed,
                'validation_error' => $validationError,
                'id_subtype' => $idSubtype ?: null,
                'has_back_side' => $hasBackSide,
                'requires_back_side' => $requiresBack,
            ];
        }

        if ($document->detected_type && $document->detected_type !== $document->category_selected) {
            $document->status = 'INVALID';
            $document->ai_feedback = 'Detected type mismatch with selected category.';
            $document->extracted_data = $extractedData;
            $document->save();
            return;
        }

        $engine = app(RuleEngine::class);
        $result = $engine->evaluate($document->category_selected, $extractedData);

        $status = $result['status'] ?? 'MANUAL_REVIEW';
        if (is_array($summary) && isset($summary['status'])) {
            $status = $summary['status'] === 'PASS' ? 'VALID' : ($summary['status'] === 'FAIL' ? 'INVALID' : 'MANUAL_REVIEW');
        }
        if ($validationFailed) {
            $status = 'MANUAL_REVIEW';
        }

        // Normalize any legacy statuses (PASS/FAIL) just in case.
        if ($status === 'PASS') {
            $status = 'VALID';
        } elseif ($status === 'FAIL') {
            $status = 'INVALID';
        }

        $confidence = $result['confidence'] ?? null;
        if ($confidence !== null) {
            if ($confidence > 0.9 && $status === 'MANUAL_REVIEW') {
                $status = 'VALID';
            } elseif ($confidence < 0.7) {
                $status = 'MANUAL_REVIEW';
            }
        }

        $document->status = $status;
        $document->ai_confidence = $confidence;
        $baseFeedback = $summary['summary'] ?? ($result['reasons'] ? implode(' ', $result['reasons']) : 'AI-analyse voltooid.');
        if ($validationFailed) {
            $baseFeedback = 'AI-validatie kon niet betrouwbaar worden uitgevoerd. Het document staat op handmatige controle.';
        }
        if ($ocrMissing) {
            $baseFeedback .= ' Let op: OCR kon geen leesbare tekst vinden. Analyse is gebaseerd op beeldherkenning en kan onvolledig zijn.';
        } elseif ($ocrBelowThreshold) {
            $baseFeedback .= ' Let op: OCR-kwaliteit is laag. Upload een scherpere scan voor betere analyse.';
        }
        if (is_array($aiResult)) {
            $details = [
                'found' => [],
                'unknown' => [],
                'evidence' => $aiResult['evidence'] ?? null,
            ];
            foreach (['bedrijfsnaam', 'kvk_nummer', 'uitgifte_datum', 'issue_date', 'expiry_date', 'document_type'] as $field) {
                if (!array_key_exists($field, $aiResult)) {
                    continue;
                }
                $value = (string) ($aiResult[$field] ?? '');
                if ($value === 'UNKNOWN' || $value === '') {
                    $details['unknown'][] = $field;
                } else {
                    $details['found'][] = $field;
                }
            }
            $extractedData['ai_details'] = $details;
            if ($details['unknown']) {
                $baseFeedback .= ' Ontbrekend: ' . implode(', ', $details['unknown']) . '.';
            }
        }
        $document->ai_feedback = $baseFeedback;
        if ($requireOcr && ($ocrMissing || $ocrBelowThreshold) && $status === 'VALID') {
            $document->status = 'MANUAL_REVIEW';
        }
        if ($requiresBack && !$hasBackSide) {
            $document->status = 'MANUAL_REVIEW';
            $document->ai_feedback = trim($document->ai_feedback . ' Voor dit subtype ID-bewijs is ook de achterzijde verplicht.');
        }
        $document->extracted_data = $extractedData;
        $document->expiry_date = $this->parseExpiryDate($extractedData['expiry_date'] ?? null);
        $document->summary_file_path = $summaryPath;
        $document->save();
    }

    private function buildRuleText(string $documentType): string
    {
        $rule = ComplianceRule::where('document_type', $documentType)->first();
        if (!$rule) {
            return '';
        }

        $lines = [];
        if (!empty($rule->required_keywords)) {
            $lines[] = 'Required keywords: ' . implode(', ', $rule->required_keywords);
        }
        if ($rule->max_age_months) {
            $lines[] = 'Max age (months): ' . $rule->max_age_months;
        }
        if (!empty($rule->constraints)) {
            $lines[] = 'Constraints: ' . json_encode($rule->constraints);
        }
        return implode("\n", $lines);
    }

    private function parseExpiryDate(?string $value): ?Carbon
    {
        if (!$value) {
            return null;
        }
        $value = trim($value);
        $formats = ['Y-m-d', 'd-m-Y', 'd/m/Y', 'Y/m/d', 'd.m.Y'];
        foreach ($formats as $format) {
            try {
                $dt = Carbon::createFromFormat($format, $value);
                if ($dt !== false) {
                    return $dt;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }
        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function geminiKeyExists(): bool
    {
        try {
            $key = \App\Models\AppSetting::getValue('gemini_api_key');
            if ($key) {
                return true;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return false;
    }

    private function geminiDebugEnabled(): bool
    {
        try {
            $val = \App\Models\AppSetting::getValue('gemini_debug_full');
            if ($val !== null) {
                return in_array($val, [true, 'true', '1', 1], true);
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return false;
    }

    private function settingBool(string $key, bool $default = false): bool
    {
        try {
            $val = \App\Models\AppSetting::getValue($key);
            if ($val !== null) {
                return in_array($val, [true, 'true', '1', 1], true);
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return $default;
    }

    private function settingNumber(string $key): ?float
    {
        try {
            $val = \App\Models\AppSetting::getValue($key);
            if ($val === null || $val === '') {
                return null;
            }
            return (float) $val;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function buildSummaryInstruction(string $documentType, string $ruleText): string
    {
        $base = "Documenttype: {$documentType}\n";
        $base .= "Maak een samenvatting die alleen over dit documenttype gaat.\n";
        $base .= "Noem gevonden velden, ontbrekende velden en verbeterpunten.\n";
        $base .= "Gebruik alleen OCR-data en bewezen informatie uit het document.\n";
        if ($ruleText !== '') {
            $base .= "\nRegels voor dit documenttype:\n{$ruleText}\n";
        }
        return $base;
    }
}
