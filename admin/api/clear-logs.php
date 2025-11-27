<?php
/**
 * Clear Logs API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    require_once __DIR__ . '/../../api/config/database.php';
    $pdo = getDBConnection();
    
    // Delete all notification logs
    $stmt = $pdo->exec("DELETE FROM notification_logs");
    
    // Also clear file logs if they exist
    $logDir = __DIR__ . '/../../api/logs';
    if (is_dir($logDir)) {
        $files = glob($logDir . '/*.log');
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'All logs cleared successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error clearing logs: ' . $e->getMessage()]);
}
