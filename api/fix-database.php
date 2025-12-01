<?php
/**
 * Fix Database Columns
 * Adds missing columns to entries table if needed
 */

header('Content-Type: application/json');

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=lotd_db;charset=utf8mb4',
        'root',
        '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    $fixes = [];
    
    // Check if entries table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'entries'");
    if ($stmt->rowCount() == 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Entries table does not exist. Please run database setup first.',
            'action' => 'Go to database-setup.html'
        ]);
        exit;
    }
    
    // Get current columns
    $stmt = $pdo->query("DESCRIBE entries");
    $existingColumns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingColumns[] = $row['Field'];
    }
    
    // Check and add missing columns
    if (!in_array('whatsapp', $existingColumns)) {
        $pdo->exec("ALTER TABLE entries ADD COLUMN whatsapp VARCHAR(20) NOT NULL AFTER name");
        $fixes[] = "Added 'whatsapp' column";
    }
    
    if (!in_array('phone', $existingColumns)) {
        $pdo->exec("ALTER TABLE entries ADD COLUMN phone VARCHAR(20) NOT NULL AFTER whatsapp");
        $fixes[] = "Added 'phone' column";
    }
    
    // Add indexes if they don't exist
    try {
        $pdo->exec("ALTER TABLE entries ADD INDEX idx_whatsapp (whatsapp)");
        $fixes[] = "Added index on 'whatsapp'";
    } catch (Exception $e) {
        // Index might already exist
    }
    
    try {
        $pdo->exec("ALTER TABLE entries ADD INDEX idx_phone (phone)");
        $fixes[] = "Added index on 'phone'";
    } catch (Exception $e) {
        // Index might already exist
    }
    
    if (empty($fixes)) {
        echo json_encode([
            'success' => true,
            'message' => 'All columns are correct. No fixes needed.',
            'existing_columns' => $existingColumns
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Database structure fixed successfully!',
            'fixes_applied' => $fixes,
            'existing_columns' => $existingColumns
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
