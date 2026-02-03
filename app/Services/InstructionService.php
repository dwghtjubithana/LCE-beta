<?php

namespace App\Services;

class InstructionService
{
    public function latest(string $dir): array
    {
        $files = glob($dir . '/*.{md,txt}', GLOB_BRACE);
        if (!$files) {
            return ['file' => null, 'version' => null, 'content' => ''];
        }

        usort($files, fn ($a, $b) => filemtime($a) <=> filemtime($b));
        $latest = end($files);
        $content = file_get_contents($latest) ?: '';
        $version = null;
        if (preg_match('/^Version:\s*(.+)$/mi', $content, $matches)) {
            $version = trim($matches[1]);
        }

        return [
            'file' => basename($latest),
            'version' => $version,
            'content' => $content,
        ];
    }
}
