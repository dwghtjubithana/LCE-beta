<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once 'config.php';

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'msg' => 'DB Verbinding mislukt: ' . $e->getMessage()]);
    exit;
}

$summaryTableExists = tableExists($pdo, 'lce_scan_summaries');

if ($summaryTableExists) {
    $sql = "SELECT l.id, l.result_status, l.scanned_at, s.summary_file_path
            FROM lce_scan_logs l
            LEFT JOIN lce_scan_summaries s ON s.scan_log_id = l.id
            ORDER BY l.scanned_at DESC
            LIMIT 20";
} else {
    $sql = "SELECT l.id, l.result_status, l.scanned_at, NULL AS summary_file_path
            FROM lce_scan_logs l
            ORDER BY l.scanned_at DESC
            LIMIT 20";
}

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll();

echo json_encode($rows);

function tableExists(PDO $pdo, $tableName) {
    $tableName = (string) $tableName;
    $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tableName));
    return (bool) $stmt->fetchColumn();
}
