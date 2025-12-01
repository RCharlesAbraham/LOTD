<?php
/**
 * Debug Send OTP - Simple test endpoint
 */

// Enable all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    echo json_encode([
        'step' => 1,
        'message' => 'Loading database config...'
    ]);
    
    require_once __DIR__ . '/config/database.php';
    
    echo json_encode([
        'step' => 2,
        'message' => 'Loading SMS helper...'
    ]);
    
    require_once __DIR__ . '/sms_helper.php';
    
    echo json_encode([
        'step' => 3,
        'message' => 'Testing database connection...'
    ]);
    
    $db = getDBConnection();
    
    echo json_encode([
        'step' => 4,
        'message' => 'All includes successful!',
        'success' => true
    ]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
