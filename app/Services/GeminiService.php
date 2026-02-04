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

    public function validateKkf(string $base64Data, string $mimeType, ?string $ocrText = null, array $additionalFiles = []): array
    {
        $model = $this->modelOrDefault('gemini_model_validation', 'gemini-2.5-flash-preview-09-2025');
        $ocrBlock = $ocrText ? "\nOCR tekst (brondata):\n" . $ocrText . "\n" : "\nOCR tekst (brondata): [LEEG]\n";
        $prompt = "Jij bent de SuriCore Compliance Engine voor Suriname. Analyseer dit KKF uittreksel.
1. Extraheer de bedrijfsnaam en het kvk_nummer.
2. Zoek de uitgiftedatum.
3. Is deze datum ouder dan 1 jaar vanaf vandaag (" . date('d-m-Y') . ")?
Gebruik alleen informatie uit de OCR tekst. Als iets ontbreekt, zet het op \"UNKNOWN\" en laat evidence leeg.
Voor elk veld geef je bewijs uit de OCR tekst.
{$ocrBlock}
Antwoord in STRIKT JSON: {
  \"bedrijfsnaam\": \"string or UNKNOWN\",
  \"kvk_nummer\": \"string or UNKNOWN\",
  \"uitgifte_datum\": \"string or UNKNOWN\",
  \"status\": \"VALID or EXPIRED or MANUAL_REVIEW\",
  \"compliance_notitie\": \"uitleg\",
  \"evidence\": {\"bedrijfsnaam\":\"...\",\"kvk_nummer\":\"...\",\"uitgifte_datum\":\"...\"}
}";

        return $this->callGemini($model, $prompt, $base64Data, $mimeType, $additionalFiles);
    }

    public function validateDocument(string $documentType, string $rulesText, string $base64Data, string $mimeType, ?string $ocrText = null, array $additionalFiles = []): array
    {
        $model = $this->modelOrDefault('gemini_model_validation', 'gemini-2.5-flash-preview-09-2025');
        $rules = $rulesText ?: 'Geen extra regels beschikbaar.';
        $ocrBlock = $ocrText ? "\nOCR tekst (brondata):\n" . $ocrText . "\n" : "\nOCR tekst (brondata): [LEEG]\n";

        $prompt = "Je bent de SuriCore Compliance Engine. Beoordeel het documenttype '{$documentType}'.
Regels:\n{$rules}\n
Gebruik alleen informatie uit de OCR tekst. Als iets ontbreekt, zet het op \"UNKNOWN\" en laat evidence leeg.
Voor elk veld geef je bewijs uit de OCR tekst.
{$ocrBlock}
Geef STRICT JSON terug met:
{
  \"summary\": \"korte samenvatting\",
  \"findings\": [\"...\"] ,
  \"missing_items\": [\"...\"] ,
  \"improvements\": [\"...\"] ,
  \"status\": \"PASS|FAIL|MANUAL_REVIEW\",
  \"extracted_data\": {\"issue_date\":\"...\", \"expiry_date\":\"...\", \"document_type\":\"...\"},
  \"evidence\": {\"issue_date\":\"...\", \"expiry_date\":\"...\", \"document_type\":\"...\"}
}";

        return $this->callGemini($model, $prompt, $base64Data, $mimeType, $additionalFiles);
    }

    public function generateComplianceSummary(string $instructionText, string $ocrText, string $originalName, string $mimeType, string $base64Data, array $additionalFiles = []): array
    {
        $model = $this->modelOrDefault('gemini_model_summary', 'gemini-2.5-flash-preview-09-2025');
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

        return $this->callGemini($model, $prompt, $base64Data, $mimeType, $additionalFiles);
    }

    public function ping(): array
    {
        $model = $this->modelOrDefault('gemini_model_summary', 'gemini-2.5-flash-preview-09-2025');
        $prompt = "Antwoord in STRIKT JSON: {\"status\":\"OK\",\"message\":\"pong\"}";

        return $this->callGeminiText($model, $prompt);
    }

    private function callGemini(string $model, string $prompt, string $base64Data, string $mimeType, array $additionalFiles = []): array
    {
        $apiKey = $this->setting('gemini_api_key');
        if (!$apiKey) {
            return ['status' => 'ERROR', 'compliance_notitie' => 'Missing GEMINI_API_KEY.'];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $apiKey;
        $parts = [
            ['text' => $prompt],
            ['inlineData' => ['mimeType' => $mimeType, 'data' => $base64Data]],
        ];
        foreach ($additionalFiles as $file) {
            $extraMime = (string) ($file['mimeType'] ?? '');
            $extraData = (string) ($file['data'] ?? '');
            if ($extraMime === '' || $extraData === '') {
                continue;
            }
            $parts[] = ['inlineData' => ['mimeType' => $extraMime, 'data' => $extraData]];
        }

        $payload = [
            'contents' => [[
                'parts' => $parts,
            ]],
            'generationConfig' => $this->buildGenerationConfig(),
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

    private function callGeminiText(string $model, string $prompt): array
    {
        $apiKey = $this->setting('gemini_api_key');
        if (!$apiKey) {
            return ['status' => 'ERROR', 'compliance_notitie' => 'Missing GEMINI_API_KEY.'];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $apiKey;
        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                ],
            ]],
            'generationConfig' => $this->buildGenerationConfig(),
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

    private function buildGenerationConfig(): array
    {
        $config = ['responseMimeType' => 'application/json'];
        $temperature = $this->setting('gemini_temperature');
        if ($temperature !== null && $temperature !== '') {
            $config['temperature'] = (float) $temperature;
        }
        $topP = $this->setting('gemini_top_p');
        if ($topP !== null && $topP !== '') {
            $config['topP'] = (float) $topP;
        }
        $maxTokens = $this->setting('gemini_max_output_tokens');
        if ($maxTokens !== null && $maxTokens !== '') {
            $config['maxOutputTokens'] = (int) $maxTokens;
        }
        return $config;
    }

    private function setting(string $key, $default = null)
    {
        try {
            return \App\Models\AppSetting::getValue($key, $default);
        } catch (\Throwable $e) {
            return $default;
        }
    }

    private function modelOrDefault(string $key, string $default): string
    {
        $value = $this->setting($key, $default);
        if (!is_string($value) || trim($value) === '') {
            return $default;
        }
        return $value;
    }
}
