<?php
/**
 * Change Password API
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

$input = json_decode(file_get_contents('php://input'), true);

$currentPassword = $input['current_password'] ?? '';
$newPassword = $input['new_password'] ?? '';

if (empty($currentPassword) || empty($newPassword)) {
    echo json_encode(['success' => false, 'message' => 'Current and new password required']);
    exit;
}

if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
    exit;
}

try {
    require_once __DIR__ . '/../../api/config/database.php';
    $pdo = getDBConnection();
    
    // Get current admin (assuming single admin for now, can be extended)
    $stmt = $pdo->query("SELECT id, password FROM admin_users WHERE is_active = 1 LIMIT 1");
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo json_encode(['success' => false, 'message' => 'Admin user not found']);
        exit;
    }
    
    // Verify current password
    if (!password_verify($currentPassword, $admin['password'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit;
    }
    
    // Update password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE admin_users SET password = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$hashedPassword, $admin['id']]);
    
    echo json_encode(['success' => true, 'message' => 'Password updated successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error updating password']);
}
