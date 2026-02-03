<?php

namespace App\Services;

use App\Models\ComplianceRule;

class RuleEngine
{
    public function evaluate(string $documentType, array $extractedData = []): array
    {
        $rule = ComplianceRule::where('document_type', $documentType)->first();
        if (!$rule) {
            return [
                'status' => 'MANUAL_REVIEW',
                'confidence' => 0.5,
                'reasons' => ['No compliance rule found for document type.'],
            ];
        }

        $reasons = [];
        $status = 'PASS';

        $issueDateRequired = (bool) ($rule->constraints['issue_date_required'] ?? false);
        if ($issueDateRequired && empty($extractedData['issue_date'])) {
            $status = 'FAIL';
            $reasons[] = 'Issue date is missing.';
        }

        $expiryDateRequired = (bool) ($rule->constraints['expiry_date_required'] ?? false);
        if ($expiryDateRequired && empty($extractedData['expiry_date'])) {
            $status = 'FAIL';
            $reasons[] = 'Expiry date is missing.';
        }

        $maxAgeMonths = $rule->max_age_months;
        if ($maxAgeMonths && !empty($extractedData['issue_date'])) {
            $issue = $this->parseIssueDate($extractedData['issue_date']);
            if ($issue) {
                $now = new \DateTime('now');
                $interval = $issue->diff($now);
                $ageMonths = ($interval->y * 12) + $interval->m;
                if ($ageMonths > (int) $maxAgeMonths) {
                    $status = 'FAIL';
                    $reasons[] = 'Issue date exceeds maximum age.';
                }
            } else {
                $status = 'MANUAL_REVIEW';
                $reasons[] = 'Unable to parse issue date.';
            }
        }

        if (!empty($extractedData['expiry_date'])) {
            $expiry = $this->parseIssueDate($extractedData['expiry_date']);
            if ($expiry && $expiry < new \DateTime('now')) {
                $status = 'FAIL';
                $reasons[] = 'Document is expired.';
            }
        }

        $ocrConfidence = $extractedData['ocr_confidence'] ?? null;
        if ($ocrConfidence !== null && (float) $ocrConfidence < 0.7) {
            return [
                'status' => 'MANUAL_REVIEW',
                'confidence' => (float) $ocrConfidence,
                'reasons' => ['OCR confidence below 70%.'],
            ];
        }

        return [
            'status' => $status,
            'confidence' => $status === 'PASS' ? 0.92 : 0.6,
            'reasons' => $reasons,
        ];
    }

    private function parseIssueDate(string $value): ?\DateTime
    {
        $value = trim($value);
        $formats = [
            'Y-m-d',
            'd-m-Y',
            'd/m/Y',
            'Y/m/d',
            'd-m-Y H:i:s',
            'Y-m-d H:i:s',
        ];

        foreach ($formats as $format) {
            $dt = \DateTime::createFromFormat($format, $value);
            if ($dt instanceof \DateTime) {
                return $dt;
            }
        }

        try {
            return new \DateTime($value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
