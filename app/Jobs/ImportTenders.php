<?php

namespace App\Jobs;

use App\Models\Tender;
use App\Services\AuditLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportTenders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $sources = config('tenders.local_sources', []);
        $imported = 0;

        foreach ($sources as $path) {
            if (!is_string($path) || !is_file($path)) {
                continue;
            }
            $raw = file_get_contents($path);
            $items = json_decode($raw ?: '[]', true);
            if (!is_array($items)) {
                continue;
            }

            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $title = trim((string) ($item['title'] ?? $item['project'] ?? ''));
                if ($title === '') {
                    continue;
                }

                $payload = [
                    'title' => $title,
                    'project' => $item['project'] ?? $title,
                    'date' => $item['date'] ?? null,
                    'client' => $item['client'] ?? null,
                    'details_url' => $item['details_url'] ?? null,
                    'attachments' => $item['attachments'] ?? null,
                    'description' => $item['description'] ?? null,
                ];

                if (!empty($payload['details_url'])) {
                    Tender::updateOrCreate(
                        ['details_url' => $payload['details_url']],
                        $payload
                    );
                } else {
                    Tender::updateOrCreate(
                        [
                            'title' => $payload['title'],
                            'client' => $payload['client'],
                            'date' => $payload['date'],
                        ],
                        $payload
                    );
                }
                $imported += 1;
            }
        }

        if ($imported > 0) {
            app(AuditLogService::class)->record(null, 'tenders.import', 'tender', null, [
                'count' => $imported,
            ]);
        }
    }
}
