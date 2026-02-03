<?php

namespace App\Console\Commands;

use App\Models\AppNotification;
use App\Models\Company;
use App\Models\Document;
use App\Services\ScoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ExpiryWatchdog extends Command
{
    protected $signature = 'lce:expiry-watchdog';
    protected $description = 'Check document expiry and create notifications.';

    public function handle(): int
    {
        $now = Carbon::now();
        $expiringThreshold = $now->copy()->addDays(30);

        $documents = Document::with('company')
            ->whereNotNull('extracted_data')
            ->whereIn('status', ['VALID', 'EXPIRING_SOON'])
            ->get();

        foreach ($documents as $document) {
            $expiry = $document->expiry_date
                ? $document->expiry_date->format('Y-m-d')
                : ($document->extracted_data['expiry_date'] ?? null);
            if (!$expiry) {
                continue;
            }

            $expiryDate = $this->parseDate($expiry);
            if (!$expiryDate) {
                continue;
            }

            if ($expiryDate->lt($now)) {
                $document->status = 'EXPIRED';
                $document->save();
                $this->recomputeScore($document->company);
                continue;
            }

            if ($expiryDate->lte($expiringThreshold)) {
                $document->status = 'EXPIRING_SOON';
                $document->save();
                $this->notifyExpiring($document);
            }
        }

        return self::SUCCESS;
    }

    private function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        $formats = ['Y-m-d', 'd-m-Y', 'd/m/Y', 'Y/m/d'];
        foreach ($formats as $format) {
            $dt = Carbon::createFromFormat($format, $value);
            if ($dt !== false) {
                return $dt;
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function notifyExpiring(Document $document): void
    {
        $company = $document->company;
        if (!$company) {
            return;
        }

        $exists = AppNotification::query()
            ->where('company_id', $company->id)
            ->where('document_id', $document->id)
            ->where('type', 'EXPIRING_SOON')
            ->first();

        if ($exists) {
            return;
        }

        AppNotification::create([
            'user_id' => $company->owner_user_id,
            'company_id' => $company->id,
            'document_id' => $document->id,
            'type' => 'EXPIRING_SOON',
            'channel' => 'email',
        ]);
    }

    private function recomputeScore(?Company $company): void
    {
        if (!$company) {
            return;
        }

        $result = app(ScoreService::class)->calculate($company);
        $company->current_score = $result['score'];
        $company->save();
    }
}
