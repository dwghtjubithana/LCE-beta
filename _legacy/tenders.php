<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database Credentials
require_once 'config.php';

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // 1. Ensure Table Exists (Auto-Migration for robustness)
    $sql = "CREATE TABLE IF NOT EXISTS tenders (
        id INT PRIMARY KEY AUTO_INCREMENT,
        project VARCHAR(255) NOT NULL,
        client VARCHAR(255),
        status VARCHAR(50) DEFAULT 'Pending',
        amount VARCHAR(50),
        start_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // 2. Fetch Data
    $stmt = $pdo->query("SELECT * FROM tenders ORDER BY created_at DESC");
    $tenders = $stmt->fetchAll();

    // 3. Return JSON
    echo json_encode($tenders);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database Error',
        'message' => $e->getMessage()
    ]);
}
?>