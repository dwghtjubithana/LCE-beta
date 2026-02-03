<?php

namespace App\Jobs;

use App\Services\GeminiService;
use App\Services\InstructionService;
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

        $filePath = storage_path('app/' . $document->source_file_url);

        if (empty($extractedData['ocr_text']) && is_file($filePath)) {
            $ocr = app(OcrService::class)->extractText($filePath);
            if (!empty($ocr['text'])) {
                $extractedData['ocr_text'] = $ocr['text'];
                $extractedData['ocr_confidence'] = $ocr['confidence'];
            }
        }

        $classifier = app(ClassifierService::class)->detect($extractedData['ocr_text'] ?? null);
        if ($classifier['type']) {
            $document->detected_type = $classifier['type'];
            $extractedData['classifier'] = $classifier;
        }

        if (is_file($filePath) && env('GEMINI_API_KEY')) {
            $base64 = base64_encode(file_get_contents($filePath));
            $mimeType = $document->mime_type ?: 'application/pdf';

            $instruction = app(InstructionService::class)->latest(base_path('resources/instructions'));
            $gemini = app(GeminiService::class);

            $aiResult = $document->category_selected === 'KKF Uittreksel'
                ? $gemini->validateKkf($base64, $mimeType)
                : $gemini->validateDocument(
                    $document->category_selected,
                    $this->buildRuleText($document->category_selected),
                    $base64,
                    $mimeType
                );
            $summary = $gemini->generateComplianceSummary(
                $instruction['content'],
                $extractedData['ocr_text'] ?? '',
                $document->original_filename ?? 'upload',
                $mimeType,
                $base64
            );

            $extractedData['ai_validation'] = $aiResult;
            $extractedData['ai_summary'] = $summary;
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
        $document->ai_feedback = $summary['summary'] ?? ($result['reasons'] ? implode(' ', $result['reasons']) : 'Processed via rule engine.');
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
}
