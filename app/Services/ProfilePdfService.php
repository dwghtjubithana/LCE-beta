<?php

namespace App\Services;

use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;

class ProfilePdfService
{
    public function download(Company $company)
    {
        $html = $this->buildHtml($company);
        $pdf = Pdf::loadHTML($html)->setPaper('a4');

        $filename = 'company_profile_' . $company->id . '.pdf';
        return $pdf->download($filename);
    }

    private function buildHtml(Company $company): string
    {
        $contact = $company->contact ?? [];
        $contactLines = [];
        if (!empty($contact['email'])) {
            $contactLines[] = 'Email: ' . htmlspecialchars($contact['email']);
        }
        if (!empty($contact['phone'])) {
            $contactLines[] = 'Phone: ' . htmlspecialchars($contact['phone']);
        }
        if (!empty($contact['address'])) {
            $contactLines[] = 'Address: ' . htmlspecialchars($contact['address']);
        }

        $contactHtml = $contactLines ? implode('<br>', $contactLines) : '—';

        return '<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>LCE Company Profile</title>
  <style>
    body { font-family: Arial, sans-serif; color: #0f172a; margin: 40px; }
    h1 { font-size: 22px; margin-bottom: 6px; }
    .meta { font-size: 12px; color: #64748b; margin-bottom: 24px; }
    .card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 16px; }
    .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; margin-bottom: 6px; }
    .watermark { position: fixed; bottom: 24px; right: 24px; color: #0ea5a4; font-size: 10px; opacity: 0.6; }
  </style>
</head>
<body>
  <h1>' . htmlspecialchars($company->company_name) . '</h1>
  <div class="meta">Sector: ' . htmlspecialchars($company->sector) . '</div>

  <div class="card">
    <div class="label">Experience</div>
    <div>' . htmlspecialchars($company->experience ?? '—') . '</div>
  </div>

  <div class="card">
    <div class="label">Contact</div>
    <div>' . $contactHtml . '</div>
  </div>

  <div class="watermark">Powered by SuriCore</div>
</body>
</html>';
    }
}
