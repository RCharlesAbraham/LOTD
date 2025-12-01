<?php
/**
 * Database Structure Checker
 */

header('Content-Type: application/json');

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=lotd_db;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Get entries table structure
    $stmt = $pdo->query("DESCRIBE entries");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'table' => 'entries',
        'columns' => $columns
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
