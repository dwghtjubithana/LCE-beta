<?php

namespace App\Services;

use App\Models\Company;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;

class ProfilePdfService
{
    public function download(Company $company, bool $withWatermark = true)
    {
        $html = $this->buildHtml($company, $withWatermark);
        $pdf = Pdf::loadHTML($html)->setPaper('a4');

        $filename = 'company_profile_' . $company->id . '.pdf';
        return $pdf->download($filename);
    }

    private function buildHtml(Company $company, bool $withWatermark): string
    {
        $contact = $company->contact ?? [];
        $displayName = trim((string) ($company->display_name ?: $company->company_name));
        $sector = trim((string) ($company->sector ?: 'Onbekend'));
        $experience = trim((string) ($company->experience ?: 'Niet opgegeven'));
        $address = trim((string) ($company->address ?: ($contact['address'] ?? '')));
        $status = strtoupper((string) ($company->verification_status ?: 'UNVERIFIED'));
        $slug = trim((string) ($company->public_slug ?: ''));
        $publicLink = $slug !== '' ? '/p/' . $slug : null;

        $contactLines = [];
        if (!empty($contact['email'])) {
            $contactLines[] = ['label' => 'E-mail', 'value' => (string) $contact['email']];
        }
        if (!empty($contact['phone'])) {
            $contactLines[] = ['label' => 'Telefoon', 'value' => (string) $contact['phone']];
        }
        if ($address !== '') {
            $contactLines[] = ['label' => 'Adres', 'value' => $address];
        }
        if (!empty($contact['website'])) {
            $contactLines[] = ['label' => 'Website', 'value' => (string) $contact['website']];
        }
        if (!empty($contact['whatsapp'])) {
            $contactLines[] = ['label' => 'WhatsApp', 'value' => (string) $contact['whatsapp']];
        }
        if (!empty($contact['facebook'])) {
            $contactLines[] = ['label' => 'Facebook', 'value' => (string) $contact['facebook']];
        }
        if (!empty($contact['linkedin'])) {
            $contactLines[] = ['label' => 'LinkedIn', 'value' => (string) $contact['linkedin']];
        }
        if ($publicLink) {
            $contactLines[] = ['label' => 'Publieke link', 'value' => $publicLink];
        }

        $contactHtml = $contactLines
            ? implode('', array_map(function (array $item): string {
                return '<div class="contact-row"><span class="contact-label">' . htmlspecialchars($item['label']) . '</span><span class="contact-value">' . htmlspecialchars($item['value']) . '</span></div>';
            }, $contactLines))
            : '<div class="muted">Geen contactgegevens beschikbaar.</div>';

        $logoData = $this->imageDataUri(public_path('img/logo-lce.png'));
        $profilePhotoData = $company->profile_photo_path
            ? $this->imageDataUri(storage_path('app/public/' . ltrim((string) $company->profile_photo_path, '/')))
            : null;

        $logoHtml = $logoData
            ? '<img class="brand-logo" src="' . $logoData . '" alt="SuriCore LCE logo">'
            : '<div class="brand-wordmark">SuriCore LCE</div>';

        $photoHtml = $profilePhotoData
            ? '<img class="company-photo" src="' . $profilePhotoData . '" alt="' . htmlspecialchars($displayName) . ' logo">'
            : '<div class="company-photo company-photo--placeholder">' . htmlspecialchars(strtoupper(substr($displayName, 0, 1) ?: 'S')) . '</div>';

        $watermarkHtml = $withWatermark ? '<div class="watermark">Powered by SuriCore</div>' : '';

        return '<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>SuriCore LCE Company Profile</title>
  <style>
    body { font-family: Arial, sans-serif; color: #0f172a; margin: 0; background: #f4f7f9; }
    .page { padding: 34px; }
    .hero { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #fff; border-radius: 20px; padding: 24px 28px; margin-bottom: 20px; }
    .hero-top { width: 100%; margin-bottom: 20px; }
    .brand-logo { width: 150px; height: auto; }
    .brand-wordmark { font-size: 20px; font-weight: 700; letter-spacing: 0.04em; }
    .hero-table { width: 100%; border-collapse: collapse; }
    .hero-copy { vertical-align: middle; }
    .hero-photo { width: 120px; vertical-align: middle; text-align: right; }
    h1 { font-size: 28px; margin: 0 0 8px; }
    .meta { font-size: 13px; color: #cbd5e1; margin-bottom: 10px; }
    .pill { display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; border-radius: 999px; padding: 6px 10px; background: rgba(255,255,255,0.14); color: #e2e8f0; }
    .company-photo { width: 96px; height: 96px; border-radius: 18px; object-fit: cover; background: #fff; }
    .company-photo--placeholder { line-height: 96px; text-align: center; font-size: 32px; font-weight: 800; color: #0ea5a4; }
    .grid { width: 100%; }
    .card { border: 1px solid #e2e8f0; border-radius: 16px; padding: 18px; margin-bottom: 16px; background: #ffffff; }
    .label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.08em; color: #94a3b8; margin-bottom: 10px; font-weight: 700; }
    .value { font-size: 15px; line-height: 1.6; }
    .contact-row { margin-bottom: 8px; }
    .contact-label { display: inline-block; width: 110px; color: #64748b; font-size: 12px; font-weight: 700; vertical-align: top; }
    .contact-value { display: inline-block; width: 380px; font-size: 13px; color: #0f172a; word-break: break-word; }
    .muted { color: #64748b; font-size: 13px; }
    .watermark { position: fixed; bottom: 24px; right: 24px; color: #0ea5a4; font-size: 10px; opacity: 0.6; }
  </style>
</head>
<body>
  <div class="page">
    <div class="hero">
      <div class="hero-top">' . $logoHtml . '</div>
      <table class="hero-table">
        <tr>
          <td class="hero-copy">
            <h1>' . htmlspecialchars($displayName) . '</h1>
            <div class="meta">' . htmlspecialchars($company->company_name) . ' • ' . htmlspecialchars($sector) . '</div>
            <span class="pill">' . htmlspecialchars(str_replace('_', ' ', $status)) . '</span>
          </td>
          <td class="hero-photo">' . $photoHtml . '</td>
        </tr>
      </table>
    </div>

    <div class="card">
      <div class="label">Bedrijfsprofiel</div>
      <div class="value">' . htmlspecialchars($experience) . '</div>
    </div>

    <div class="card">
      <div class="label">Contact en kanalen</div>
      <div>' . $contactHtml . '</div>
    </div>
  </div>
  ' . $watermarkHtml . '
</body>
</html>';
    }

    private function imageDataUri(string $path): ?string
    {
        if (!File::exists($path)) {
            return null;
        }

        $mime = File::mimeType($path);
        $contents = File::get($path);
        if (!$mime || $contents === false) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }
}
