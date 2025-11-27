<?php
/**
 * Setup Admin Account API
 * Creates the initial administrator account
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

$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

// Validation
if (empty($username) || strlen($username) < 3) {
    echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters']);
    exit;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Valid email is required']);
    exit;
}

if (empty($password) || strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

try {
    require_once dirname(__DIR__) . '/../api/config/database.php';
    $pdo = getDBConnection();

    // Check if admin already exists
    $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Admin user already exists']);
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert admin user
    $stmt = $pdo->prepare("
        INSERT INTO admin_users (username, email, password, is_active, created_at)
        VALUES (?, ?, ?, 1, NOW())
    ");
    $stmt->execute([$username, $email, $hashedPassword]);

    // Insert default settings
    $defaultSettings = [
        ['otp_length', '6'],
        ['otp_expiry_minutes', '10'],
        ['max_otp_attempts', '3'],
        ['email_service', 'smtp'],
        ['sms_service', 'log'],
        ['site_name', 'LOTD'],
        ['setup_completed', '1'],
        ['setup_date', date('Y-m-d H:i:s')]
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($defaultSettings as $setting) {
        $stmt->execute($setting);
    }

    // Create installed marker file
    $installedFile = dirname(__DIR__) . '/../api/config/installed.php';
    file_put_contents($installedFile, "<?php\n// Installation completed on " . date('Y-m-d H:i:s') . "\ndefine('INSTALLED', true);\n");

    echo json_encode([
        'success' => true,
        'message' => 'Admin account created successfully'
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
