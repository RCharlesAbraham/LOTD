<?php
/**
 * Admin Login API
 * 
 * POST /admin/api/login.php
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
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['username']) || empty($input['password'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username and password are required']);
    exit;
}

$username = trim($input['username']);
$password = $input['password'];

// Check if database config exists
$configFile = __DIR__ . '/../../api/config/database.php';
if (!file_exists($configFile)) {
    echo json_encode(['success' => false, 'message' => 'System not configured. Please run setup first.', 'redirect' => 'setup.html']);
    exit;
}

require_once $configFile;

try {
    $pdo = getDBConnection();
    
    // Find admin user in database
    $stmt = $pdo->prepare("SELECT id, username, email, password FROM admin_users WHERE username = ? AND is_active = 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password'])) {
        // Update last login
        $updateStmt = $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
        $updateStmt->execute([$admin['id']]);
        
        // Generate token
        $token = bin2hex(random_bytes(32));
        
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'email' => $admin['email']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    }
} catch (PDOException $e) {
    // Database not setup yet, allow default login
    if ($username === 'admin' && $password === 'admin123') {
        $token = bin2hex(random_bytes(32));
        echo json_encode([
            'success' => true,
            'message' => 'Login successful (default credentials)',
            'token' => $token,
            'user' => ['username' => $username, 'name' => 'Administrator']
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    }
}
