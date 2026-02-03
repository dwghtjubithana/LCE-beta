<?php

namespace App\Services;

use GuzzleHttp\Client;

class GeminiService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 60,
        ]);
    }

    public function validateKkf(string $base64Data, string $mimeType): array
    {
        $model = env('GEMINI_MODEL_VALIDATION', 'gemini-2.5-flash-preview-09-2025');
        $prompt = "Jij bent de SuriCore Compliance Engine voor Suriname. Analyseer dit KKF uittreksel.
1. Extraheer de bedrijfsnaam en het kvk_nummer.
2. Zoek de uitgiftedatum.
3. Is deze datum ouder dan 1 jaar vanaf vandaag (" . date('d-m-Y') . ")?
Antwoord in STRIKT JSON: {
  \"bedrijfsnaam\": \"string\",
  \"kvk_nummer\": \"string\",
  \"uitgifte_datum\": \"string\",
  \"status\": \"VALID of EXPIRED\",
  \"compliance_notitie\": \"uitleg\"
}";

        return $this->callGemini($model, $prompt, $base64Data, $mimeType);
    }

    public function validateDocument(string $documentType, string $rulesText, string $base64Data, string $mimeType): array
    {
        $model = env('GEMINI_MODEL_VALIDATION', 'gemini-2.5-flash-preview-09-2025');
        $rules = $rulesText ?: 'Geen extra regels beschikbaar.';

        $prompt = "Je bent de SuriCore Compliance Engine. Beoordeel het documenttype '{$documentType}'.
Regels:\n{$rules}\n
Geef STRICT JSON terug met:
{
  \"summary\": \"korte samenvatting\",
  \"findings\": [\"...\"] ,
  \"missing_items\": [\"...\"] ,
  \"improvements\": [\"...\"] ,
  \"status\": \"PASS|FAIL|MANUAL_REVIEW\",
  \"extracted_data\": {\"issue_date\":\"...\", \"expiry_date\":\"...\", \"document_type\":\"...\"}
}";

        return $this->callGemini($model, $prompt, $base64Data, $mimeType);
    }

    public function generateComplianceSummary(string $instructionText, string $ocrText, string $originalName, string $mimeType, string $base64Data): array
    {
        $model = env('GEMINI_MODEL_SUMMARY', 'gemini-2.5-flash-preview-09-2025');
        $rules = $instructionText ?: "Geen instructies gevonden. Geef aan wat ontbreekt in de regels.";

        $prompt = "Je bent de SuriCore Compliance Engine. Gebruik de onderstaande instructies om het document te beoordelen.
Instructies:\n" . $rules . "\n
Document metadata:\n- Bestandsnaam: " . $originalName . "\n- MIME type: " . $mimeType . "\n
OCR tekst (indien beschikbaar):\n" . ($ocrText ?: 'Geen OCR tekst') . "\n
Geef STRICT JSON terug met:
{
  \"summary\": \"korte samenvatting\",
  \"findings\": [\"...\"] ,
  \"missing_items\": [\"...\"] ,
  \"improvements\": [\"...\"] ,
  \"status\": \"PASS|FAIL|MANUAL_REVIEW\"
}";

        return $this->callGemini($model, $prompt, $base64Data, $mimeType);
    }

    private function callGemini(string $model, string $prompt, string $base64Data, string $mimeType): array
    {
        $apiKey = env('GEMINI_API_KEY');
        if (!$apiKey) {
            return ['status' => 'ERROR', 'compliance_notitie' => 'Missing GEMINI_API_KEY.'];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $apiKey;
        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                    ['inlineData' => ['mimeType' => $mimeType, 'data' => $base64Data]],
                ],
            ]],
            'generationConfig' => ['responseMimeType' => 'application/json'],
        ];

        try {
            $response = $this->client->post($url, [
                'headers' => ['Content-Type' => 'application/json'],
                'json' => $payload,
            ]);
            $decoded = json_decode((string) $response->getBody(), true);
            $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            $parsed = json_decode($text, true);
            return is_array($parsed) ? $parsed : ['status' => 'ERROR', 'compliance_notitie' => 'Unexpected Gemini response.'];
        } catch (\Throwable $e) {
            return ['status' => 'ERROR', 'compliance_notitie' => $e->getMessage()];
        }
    }
}
