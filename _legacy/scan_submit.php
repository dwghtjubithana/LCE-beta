<?php
/**
 * scan_submit.php - DEFINITIEVE VERSIE
 * Handelt upload, Imagick processing en ECHTE Gemini AI validatie af.
 * Gekoppeld aan de database dbmyu6uoo7735j.
 */

header('Content-Type: application/json');

// 1. Configuratie & Database
require_once 'config.php';
$dompdfAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($dompdfAutoload)) {
    require_once $dompdfAutoload;
}
$apiKey = $geminiApiKey;
if (!$apiKey) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'msg' => 'Gemini API key ontbreekt.']);
    exit;
}

// --- TEST MODE ---
// Roep dit script aan via scan_submit.php?test=1 om de API-sleutel direct te verifiëren.
if (isset($_GET['test'])) {
    $testPixel = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==";
    $result = validateKKFWithAI($testPixel, "image/png", $apiKey);
    echo json_encode([
        "connection_test" => "Gemini API Call",
        "response_from_ai" => $result,
        "advice" => "Als je hierboven JSON data ziet, is de 'Nieuwe Wereld' verbinding actief!"
    ]);
    exit;
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'msg' => 'DB Verbinding mislukt: ' . $e->getMessage()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // --- STAP 1: Bestand verwerken ---
        $uploadDir = __DIR__ . '/uploads/scans/';
        $summaryDir = __DIR__ . '/uploads/summaries/';
        $uploadDirRel = 'uploads/scans/';
        $summaryDirRel = 'uploads/summaries/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        if (!is_dir($summaryDir)) mkdir($summaryDir, 0755, true);
        if (!is_writable($uploadDir) || !is_writable($summaryDir)) {
            throw new Exception('Upload directory is not writable.');
        }

        if (!isset($_FILES['scan_image'])) throw new Exception('Geen scan_image ontvangen.');

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($_FILES['scan_image']['tmp_name']);
        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
        ];
        if (!isset($allowedMimes[$detectedMime])) {
            throw new Exception('Bestandstype niet ondersteund.');
        }

        $originalName = $_FILES['scan_image']['name'] ?? 'upload';
        $fileSize = $_FILES['scan_image']['size'] ?? null;
        $ext = $allowedMimes[$detectedMime];
        $fileToken = bin2hex(random_bytes(6));
        $scanFileName = 'scan_' . time() . '_' . $fileToken . '.' . $ext;
        $targetPath = $uploadDir . $scanFileName;
        $targetPathRel = $uploadDirRel . $scanFileName;

        $storedMime = $detectedMime;
        if (in_array($detectedMime, ['image/jpeg', 'image/png'], true)) {
            $scanFileName = 'scan_' . time() . '_' . $fileToken . '.jpg';
            $targetPath = $uploadDir . $scanFileName;
            $targetPathRel = $uploadDirRel . $scanFileName;
            $storedMime = 'image/jpeg';
            $imagick = new Imagick($_FILES['scan_image']['tmp_name']);
            $imagick->resizeImage(1280, 1280, Imagick::FILTER_LANCZOS, 1, true);
            $imagick->setImageFormat('jpeg');
            $imagick->stripImage(); // EXIF data verwijderen voor privacy
            if (!$imagick->writeImage($targetPath)) {
                throw new Exception('Image processing failed while saving scan.');
            }
            $imagick->clear();
            if (!file_exists($targetPath)) {
                throw new Exception('Processed image was not saved.');
            }
        } else {
            if (!move_uploaded_file($_FILES['scan_image']['tmp_name'], $targetPath)) {
                throw new Exception('Upload mislukt bij verplaatsen van bestand.');
            }
        }

        // --- STAP 2: De Echte AI Call naar Gemini ---
        error_log("[LCE] AI Analyse gestart voor bestand: " . $targetPath);
        $fileData = base64_encode(file_get_contents($targetPath));
        $aiResult = validateKKFWithAI($fileData, $storedMime, $apiKey);

        $instructionInfo = loadLatestInstruction(__DIR__ . '/instructions');
        $ocrText = $_POST['ocr_text'] ?? '';
        $summaryResult = generateComplianceSummary(
            $instructionInfo['content'],
            $ocrText,
            $originalName,
            $storedMime,
            $apiKey,
            $fileData
        );

        // --- STAP 3: Database Mapping ---
        $inspectorId = resolveForeignKey($pdo, 'users', $_POST['inspector_id'] ?? 1);
        $supplierId  = resolveForeignKey($pdo, 'suppliers', $_POST['supplier_id'] ?? 1);
        $geoLocation = $_POST['geo_location'] ?? '0.0,0.0';
        
        // Map de AI status naar de Database ENUM ('PASS','FAIL','MANUAL_REVIEW')
        $dbStatus = 'MANUAL_REVIEW';
        if (isset($aiResult['status'])) {
            if ($aiResult['status'] === 'VALID') $dbStatus = 'PASS';
            if ($aiResult['status'] === 'EXPIRED') $dbStatus = 'FAIL';
        }
        if (isset($summaryResult['status'])) {
            if ($summaryResult['status'] === 'PASS') $dbStatus = 'PASS';
            if ($summaryResult['status'] === 'FAIL') $dbStatus = 'FAIL';
        }

        // De AI extracted data opslaan in de ppe_detected JSON kolom
        $documentJson = json_encode($aiResult);

        $sql = "INSERT INTO lce_scan_logs 
                (inspector_id, supplier_id, image_path, geo_location, ai_confidence, ppe_detected, result_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $inspectorId, 
            $supplierId, 
            $targetPathRel, 
            $geoLocation, 
            98.50, // Confidence score
            $documentJson, 
            $dbStatus
        ]);

        $newId = $pdo->lastInsertId();

        // --- STAP 4: Summary opslaan ---
        $summaryHtmlFileName = 'summary_' . $newId . '_' . time() . '.html';
        $summaryHtmlPath = $summaryDir . $summaryHtmlFileName;
        $summaryPdfFileName = 'summary_' . $newId . '_' . time() . '.pdf';
        $summaryPdfPath = $summaryDir . $summaryPdfFileName;
        $summaryPdfPathRel = $summaryDirRel . $summaryPdfFileName;

        $summaryHtml = buildSummaryHtml($summaryResult, $instructionInfo, $originalName, $storedMime);
        if (file_put_contents($summaryHtmlPath, $summaryHtml) === false) {
            throw new Exception('Unable to write summary HTML.');
        }

        $pdfGenerated = generatePdfFromHtml($summaryHtml, $summaryPdfPath);
        if (!$pdfGenerated) {
            $summaryLines = htmlToLines($summaryHtml);
            createSimplePdf($summaryPdfPath, $summaryLines);
        }
        $summaryPathRel = $summaryPdfPathRel;

        $summaryStored = false;
        if (tableExists($pdo, 'lce_scan_summaries')) {
            $summarySql = "INSERT INTO lce_scan_summaries
                (scan_log_id, instruction_file, instruction_version, original_filename, mime_type, file_size, ocr_text, gemini_summary, gemini_raw_json, summary_file_path)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $summaryStmt = $pdo->prepare($summarySql);
            $summaryStmt->execute([
                $newId,
                $instructionInfo['file'],
                $instructionInfo['version'],
                $originalName,
                $storedMime,
                $fileSize,
                $ocrText,
                $summaryResult['summary'] ?? null,
                json_encode($summaryResult),
            $summaryPathRel
        ]);
            $summaryStored = true;
        }

        // --- STAP 5: Resultaat terugsturen naar de App ---
        echo json_encode([
            'status' => 'success',
            'scan_id' => 'DOC-' . str_pad($newId, 5, '0', STR_PAD_LEFT),
            'ai_data' => $aiResult,
            'summary' => $summaryResult,
            'summary_file_path' => $summaryPathRel,
            'summary_stored' => $summaryStored,
            'db_status' => $dbStatus,
            'msg' => 'Document succesvol geanalyseerd en samengevat.'
        ]);

    } catch (Exception $e) {
        error_log("[LCE ERROR] " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
}

/**
 * Functie voor de communicatie met Google Gemini
 */
function validateKKFWithAI($base64Image, $mimeType, $apiKey) {
    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-09-2025:generateContent?key=" . $apiKey;
    
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

    $payload = [
        "contents" => [[
            "parts" => [
                ["text" => $prompt],
                ["inlineData" => ["mimeType" => $mimeType, "data" => $base64Image]]
            ]
        ]],
        "generationConfig" => ["responseMimeType" => "application/json"]
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Om SSL issues op lokale servers te omzeilen

    $result = curl_exec($ch);
    if (curl_errno($ch)) return ["status" => "ERROR", "compliance_notitie" => curl_error($ch)];
    curl_close($ch);
    
    $decoded = json_decode($result, true);
    $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? "{}";
    return json_decode($text, true);
}

function loadLatestInstruction($dir) {
    $files = glob($dir . '/*.{md,txt}', GLOB_BRACE);
    if (!$files) {
        return ['file' => null, 'version' => null, 'content' => ''];
    }
    usort($files, function ($a, $b) {
        return filemtime($a) <=> filemtime($b);
    });
    $latest = end($files);
    $content = file_get_contents($latest) ?: '';
    $version = null;
    if (preg_match('/^Version:\s*(.+)$/mi', $content, $matches)) {
        $version = trim($matches[1]);
    }
    return [
        'file' => basename($latest),
        'version' => $version,
        'content' => $content
    ];
}

function generateComplianceSummary($instructionText, $ocrText, $originalName, $mimeType, $apiKey, $base64Data) {
    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-09-2025:generateContent?key=" . $apiKey;
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

    $payload = [
        "contents" => [[
            "parts" => [
                ["text" => $prompt],
                ["inlineData" => ["mimeType" => $mimeType, "data" => $base64Data]]
            ]
        ]],
        "generationConfig" => ["responseMimeType" => "application/json"]
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        return ["status" => "MANUAL_REVIEW", "summary" => "Gemini fout: " . curl_error($ch)];
    }
    curl_close($ch);

    $decoded = json_decode($result, true);
    $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? "{}";
    $parsed = json_decode($text, true);
    if (!is_array($parsed)) {
        return ["status" => "MANUAL_REVIEW", "summary" => "Onverwachte Gemini response."];
    }
    return $parsed;
}

function buildSummaryHtml($summaryResult, $instructionInfo, $originalName, $mimeType) {
    $status = $summaryResult['status'] ?? 'MANUAL_REVIEW';
    $summary = $summaryResult['summary'] ?? 'Geen samenvatting ontvangen.';
    $findings = $summaryResult['findings'] ?? [];
    $missing = $summaryResult['missing_items'] ?? [];
    $improvements = $summaryResult['improvements'] ?? [];

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
    File: ' . htmlspecialchars($originalName) . '<br>
    MIME: ' . htmlspecialchars($mimeType) . '<br>
    Instruction: ' . htmlspecialchars($instructionInfo['file'] ?: 'none') . '<br>
    Instruction Version: ' . htmlspecialchars($instructionInfo['version'] ?: 'unknown') . '
  </div>

  <div class="card">
    <div class="label">Status</div>
    <div class="status ' . $statusClass . '">' . htmlspecialchars($status) . '</div>
  </div>

  <div class="card">
    <div class="label">Summary</div>
    <p>' . htmlspecialchars($summary) . '</p>
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

function tableExists(PDO $pdo, $tableName) {
    $tableName = (string) $tableName;
    $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tableName));
    return (bool) $stmt->fetchColumn();
}

function resolveForeignKey(PDO $pdo, $table, $preferredId) {
    $id = (int) $preferredId;
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn()) {
            return $id;
        }
    }

    $stmt = $pdo->query("SELECT id FROM {$table} ORDER BY id ASC LIMIT 1");
    $fallback = $stmt->fetchColumn();
    if ($fallback) {
        return (int) $fallback;
    }

    throw new Exception("Missing required {$table} record for scanner.");
}

function generatePdfFromHtml($html, $pdfPath) {
    if (class_exists('Dompdf\\Dompdf')) {
        $dompdf = new Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4');
        $dompdf->render();
        return file_put_contents($pdfPath, $dompdf->output()) !== false;
    }

    if (!function_exists('shell_exec')) {
        return false;
    }

    $wkhtmltopdf = getenv('WKHTMLTOPDF_PATH') ?: 'wkhtmltopdf';
    $tmpHtml = $pdfPath . '.tmp.html';
    if (file_put_contents($tmpHtml, $html) === false) {
        return false;
    }
    $cmd = escapeshellcmd($wkhtmltopdf) . ' ' . escapeshellarg($tmpHtml) . ' ' . escapeshellarg($pdfPath) . ' 2>&1';
    $output = shell_exec($cmd);
    @unlink($tmpHtml);

    return file_exists($pdfPath) && filesize($pdfPath) > 0;
}

function htmlToLines($html) {
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
    $words = explode(' ', $text);
    $lines = [];
    $line = '';
    foreach ($words as $word) {
        if (strlen($line . ' ' . $word) > 90) {
            $lines[] = trim($line);
            $line = $word;
        } else {
            $line .= ' ' . $word;
        }
    }
    if (trim($line) !== '') {
        $lines[] = trim($line);
    }
    return $lines ?: ['No summary available.'];
}

function escapePdfText($text) {
    $text = str_replace('\\', '\\\\', $text);
    $text = str_replace('(', '\\(', $text);
    $text = str_replace(')', '\\)', $text);
    return $text;
}

function createSimplePdf($path, array $lines) {
    $fontSize = 12;
    $lineHeight = 16;
    $startY = 760;

    $contentLines = [];
    $contentLines[] = 'BT';
    $contentLines[] = '/F1 ' . $fontSize . ' Tf';
    $contentLines[] = '72 ' . $startY . ' Td';

    $first = true;
    foreach ($lines as $line) {
        $safe = escapePdfText($line);
        if ($first) {
            $contentLines[] = '(' . $safe . ') Tj';
            $first = false;
        } else {
            $contentLines[] = '0 -' . $lineHeight . ' Td';
            $contentLines[] = '(' . $safe . ') Tj';
        }
    }

    $contentLines[] = 'ET';
    $contentStream = implode("\n", $contentLines);

    $objects = [];
    $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
    $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
    $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n";
    $objects[] = "4 0 obj\n<< /Length " . strlen($contentStream) . " >>\nstream\n" . $contentStream . "\nendstream\nendobj\n";
    $objects[] = "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $obj) {
        $offsets[] = strlen($pdf);
        $pdf .= $obj;
    }

    $xrefStart = strlen($pdf);
    $size = count($objects) + 1;
    $pdf .= "xref\n0 " . $size . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i < $size; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }

    $pdf .= "trailer\n<< /Size " . $size . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xrefStart . "\n%%EOF\n";

    if (file_put_contents($path, $pdf) === false) {
        throw new Exception('Unable to write summary PDF.');
    }
}
