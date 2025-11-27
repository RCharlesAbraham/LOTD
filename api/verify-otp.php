<?php
/**
 * Verify OTP API Endpoint
 * 
 * POST /api/verify-otp.php
 * 
 * Request Body:
 * {
 *   "entry_id": 1,
 *   "otp": "123456"
 * }
 */

require_once __DIR__ . '/config/database.php';

setCorsHeaders();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (empty($input['entry_id']) || empty($input['otp'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Entry ID and OTP are required']);
    exit;
}

$entryId = intval($input['entry_id']);
$otp = trim($input['otp']);

// Validate OTP format (6 digits)
if (!preg_match('/^\d{6}$/', $otp)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid OTP format']);
    exit;
}

try {
    $db = getDBConnection();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Rate limiting: Check if too many verification attempts
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM otp_attempts 
        WHERE ip_address = ? 
        AND attempt_type = 'verify' 
        AND is_successful = 0
        AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
    ");
    $stmt->execute([$ipAddress]);
    $attempts = $stmt->fetch();
    
    if ($attempts['count'] >= 5) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many failed attempts. Please wait 15 minutes.']);
        exit;
    }
    
    // Get the entry
    $stmt = $db->prepare("SELECT * FROM entries WHERE id = ?");
    $stmt->execute([$entryId]);
    $entry = $stmt->fetch();
    
    if (!$entry) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Entry not found']);
        exit;
    }
    
    // Check if already verified
    if ($entry['is_verified']) {
        echo json_encode([
            'success' => true,
            'message' => 'Entry already verified',
            'data' => [
                'entry_number' => $entry['entry_number'],
                'name' => $entry['name'],
                'email' => $entry['email'],
                'phone' => $entry['phone'],
                'verified_at' => $entry['verified_at']
            ]
        ]);
        exit;
    }
    
    // Get valid OTP for this entry
    $stmt = $db->prepare("
        SELECT * FROM otps 
        WHERE entry_id = ? 
        AND is_used = 0 
        AND expires_at > NOW()
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$entryId]);
    $otpRecord = $stmt->fetch();
    
    if (!$otpRecord) {
        // Log failed attempt
        $stmt = $db->prepare("INSERT INTO otp_attempts (entry_id, ip_address, attempt_type, is_successful) VALUES (?, ?, 'verify', 0)");
        $stmt->execute([$entryId, $ipAddress]);
        
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'OTP expired. Please request a new one.']);
        exit;
    }
    
    // Check attempts on this OTP
    if ($otpRecord['attempts'] >= 3) {
        // Mark OTP as used (invalidate it)
        $stmt = $db->prepare("UPDATE otps SET is_used = 1 WHERE id = ?");
        $stmt->execute([$otpRecord['id']]);
        
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Too many attempts. Please request a new OTP.']);
        exit;
    }
    
    // Verify OTP
    if ($otpRecord['otp_code'] !== $otp) {
        // Increment attempts
        $stmt = $db->prepare("UPDATE otps SET attempts = attempts + 1 WHERE id = ?");
        $stmt->execute([$otpRecord['id']]);
        
        // Log failed attempt
        $stmt = $db->prepare("INSERT INTO otp_attempts (entry_id, ip_address, attempt_type, is_successful) VALUES (?, ?, 'verify', 0)");
        $stmt->execute([$entryId, $ipAddress]);
        
        $remainingAttempts = 3 - ($otpRecord['attempts'] + 1);
        
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid OTP. ' . ($remainingAttempts > 0 ? "$remainingAttempts attempts remaining." : 'Please request a new OTP.')
        ]);
        exit;
    }
    
    // OTP is valid - Mark entry as verified
    $verifiedAt = date('Y-m-d H:i:s');
    
    $db->beginTransaction();
    
    try {
        // Update entry as verified
        $stmt = $db->prepare("UPDATE entries SET is_verified = 1, verified_at = ? WHERE id = ?");
        $stmt->execute([$verifiedAt, $entryId]);
        
        // Mark OTP as used
        $stmt = $db->prepare("UPDATE otps SET is_used = 1 WHERE id = ?");
        $stmt->execute([$otpRecord['id']]);
        
        // Log successful verification
        $stmt = $db->prepare("INSERT INTO otp_attempts (entry_id, ip_address, attempt_type, is_successful) VALUES (?, ?, 'verify', 1)");
        $stmt->execute([$entryId, $ipAddress]);
        
        $db->commit();
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'OTP verified successfully',
            'data' => [
                'entry_number' => $entry['entry_number'],
                'name' => $entry['name'],
                'email' => $entry['email'],
                'phone' => $entry['phone'],
                'verified_at' => $verifiedAt
            ]
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log('Verify OTP Error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during verification. Please try again.'
    ]);
}
