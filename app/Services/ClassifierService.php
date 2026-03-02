<?php

namespace App\Services;

class ClassifierService
{
    private array $typeKeywords = [
        'KKF Uittreksel' => ['KKF', 'KVK', 'Handelsregister', 'Uittreksel'],
        'CRIB' => ['CRIB', 'Credit Bureau', 'Schulden', 'Krediet'],
        'UBO' => ['UBO', 'Ultimate Beneficial Owner', 'Beneficial Owner'],
        'HSE' => ['HSE', 'Health Safety Environment', 'Safety Plan', 'Incident'],
        'ISO' => ['ISO', 'ISO 9001', 'ISO 14001', 'ISO 45001'],
        'IOGP' => ['IOGP', 'IOGP-423', 'International Association of Oil & Gas Producers'],
        'Vergunning' => ['Vergunning', 'Permit', 'License'],
        'Belastingverklaring' => ['Belasting', 'Tax', 'Aanslag', 'Verklaring'],
        'ID Bewijs' => ['ID', 'Identiteit', 'Passport', 'Rijbewijs'],
    ];

    public function detect(?string $ocrText): array
    {
        if (!$ocrText) {
            return ['type' => null, 'confidence' => 0.0];
        }

        $haystack = mb_strtoupper($ocrText);
        $bestType = null;
        $bestScore = 0;

        foreach ($this->typeKeywords as $type => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (str_contains($haystack, mb_strtoupper($keyword))) {
                    $score++;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestType = $type;
            }
        }

        $confidence = $bestScore > 0 ? min(0.95, 0.5 + ($bestScore * 0.15)) : 0.0;

        return [
            'type' => $bestType,
            'confidence' => $confidence,
        ];
    }
}
