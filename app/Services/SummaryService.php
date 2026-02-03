<?php

namespace App\Services;

use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class SummaryService
{
    public function generate(Document $document, array $summary, array $instruction): ?string
    {
        $html = $this->buildHtml($document, $summary, $instruction);
        $baseName = 'summary_' . $document->id . '_' . time();
        $dir = 'uploads/summaries';

        Storage::disk('local')->put($dir . '/' . $baseName . '.html', $html);

        try {
            $pdf = Pdf::loadHTML($html)->setPaper('a4');
            Storage::disk('local')->put($dir . '/' . $baseName . '.pdf', $pdf->output());
            return $dir . '/' . $baseName . '.pdf';
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function buildHtml(Document $document, array $summary, array $instruction): string
    {
        $status = $summary['status'] ?? 'MANUAL_REVIEW';
        $summaryText = $summary['summary'] ?? 'Geen samenvatting ontvangen.';
        $findings = $summary['findings'] ?? [];
        $missing = $summary['missing_items'] ?? [];
        $improvements = $summary['improvements'] ?? [];

        $findingsHtml = $findings ? '<ul><li>' . implode('</li><li>', array_map('htmlspecialchars', $findings)) . '</li></ul>' : '<p>- none</p>';
        $missingHtml = $missing ? '<ul><li>' . implode('</li><li>', array_map('htmlspecialchars', $missing)) . '</li></ul>' : '<p>- none</p>';
        $improvementsHtml = $improvements ? '<ul><li>' . implode('</li><li>', array_map('htmlspecialchars', $improvements)) . '</li></ul>' : '<p>- none</p>';

        $statusClass = $status === 'FAIL' ? 'status-fail' : ($status === 'PASS' ? 'status-pass' : 'status-review');

        return '<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>LCE Compliance Summary</title>
  <style>
    body { font-family: Arial, sans-serif; color: #1f2933; margin: 40px; }
    h1 { font-size: 20px; margin-bottom: 8px; }
    .meta { font-size: 12px; color: #5f6c7b; margin-bottom: 24px; }
    .card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; margin-bottom: 16px; }
    .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; margin-bottom: 6px; }
    .status { font-weight: bold; padding: 6px 10px; border-radius: 6px; display: inline-block; }
    .status-pass { background: #ecfdf3; color: #047857; }
    .status-fail { background: #fef2f2; color: #b91c1c; }
    .status-review { background: #fffbeb; color: #b45309; }
    ul { margin: 0 0 0 18px; padding: 0; }
  </style>
</head>
<body>
  <h1>LCE Compliance Summary</h1>
  <div class="meta">
    File: ' . htmlspecialchars($document->original_filename ?? 'upload') . '<br>
    MIME: ' . htmlspecialchars($document->mime_type ?? '') . '<br>
    Instruction: ' . htmlspecialchars($instruction['file'] ?? 'none') . '<br>
    Instruction Version: ' . htmlspecialchars($instruction['version'] ?? 'unknown') . '
  </div>

  <div class="card">
    <div class="label">Status</div>
    <div class="status ' . $statusClass . '">' . htmlspecialchars($status) . '</div>
  </div>

  <div class="card">
    <div class="label">Summary</div>
    <p>' . htmlspecialchars($summaryText) . '</p>
  </div>

  <div class="card">
    <div class="label">Findings</div>
    ' . $findingsHtml . '
  </div>

  <div class="card">
    <div class="label">Missing Items</div>
    ' . $missingHtml . '
  </div>

  <div class="card">
    <div class="label">Improvements</div>
    ' . $improvementsHtml . '
  </div>
</body>
</html>';
    }
}
