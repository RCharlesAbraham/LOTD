<?php
/**
 * Check Setup Status API
 * Determines if the system has already been set up
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$is_setup = false;

// Check for installed marker file
$configFile = dirname(__DIR__) . '/../api/config/installed.php';
if (file_exists($configFile)) {
    $is_setup = true;
}

// Also check if admin_users table has data
if (!$is_setup) {
    $dbConfigFile = dirname(__DIR__) . '/../api/config/database.php';
    if (file_exists($dbConfigFile)) {
        try {
            // Direct connection without using the function that might exit
            $pdo = new PDO(
                'mysql:host=localhost;dbname=lotd_db;charset=utf8mb4',
                'root',
                '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            $stmt = $pdo->query("SELECT COUNT(*) FROM admin_users");
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                $is_setup = true;
            }
        } catch (Exception $e) {
            // Database not ready, not setup
            $is_setup = false;
        }
    }
}

echo json_encode([
    'success' => true,
    'is_setup' => $is_setup
]);
