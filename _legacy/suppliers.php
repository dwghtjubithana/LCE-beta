<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Database Credentials
require_once 'config.php';

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // 1. Ensure Table Exists
    $sql = "CREATE TABLE IF NOT EXISTS suppliers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        type VARCHAR(100),
        status VARCHAR(50) DEFAULT 'Active',
        lce_certified TINYINT(1) DEFAULT 0,
        contact_email VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // 2. Fetch Data
    $stmt = $pdo->query("SELECT * FROM suppliers ORDER BY name ASC");
    $suppliers = $stmt->fetchAll();

    // 3. Return JSON
    echo json_encode($suppliers);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database Error',
        'message' => $e->getMessage()
    ]);
}
?>