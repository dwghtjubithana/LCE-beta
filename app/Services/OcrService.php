<?php

namespace App\Services;

class OcrService
{
    public function extractText(string $path): array
    {
        if (!env('OCR_ENABLED', false)) {
            return ['text' => null, 'confidence' => null];
        }

        if (!is_file($path)) {
            return ['text' => null, 'confidence' => null];
        }

        $binary = env('TESSERACT_PATH', 'tesseract');
        $cmdText = escapeshellcmd($binary) . ' ' . escapeshellarg($path) . ' stdout 2>/dev/null';
        $output = shell_exec($cmdText);

        $cmdTsv = escapeshellcmd($binary) . ' ' . escapeshellarg($path) . ' stdout tsv 2>/dev/null';
        $tsv = shell_exec($cmdTsv);

        if (!$output) {
            return ['text' => null, 'confidence' => null];
        }

        return [
            'text' => trim($output),
            'confidence' => $this->parseTsvConfidence($tsv ?: ''),
        ];
    }

    public function parseTsvConfidence(string $tsv): ?float
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($tsv));
        if (!$lines || count($lines) <= 1) {
            return null;
        }

        $sum = 0.0;
        $count = 0;
        foreach ($lines as $i => $line) {
            if ($i === 0 || trim($line) === '') {
                continue; // header
            }
            $cols = explode("\t", $line);
            if (count($cols) < 11) {
                continue;
            }
            $conf = (float) $cols[10];
            if ($conf < 0) {
                continue;
            }
            $sum += $conf;
            $count++;
        }

        if ($count === 0) {
            return null;
        }

        return round($sum / $count, 2);
    }
}
