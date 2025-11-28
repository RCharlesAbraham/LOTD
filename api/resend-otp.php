<?php
/**
 * Resend OTP API Endpoint
 * 
 * POST /api/resend-otp.php
 * 
 * Request Body:
 * {
 *   "entry_id": 1
 * }
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/sms.php';

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
if (empty($input['entry_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Entry ID is required']);
    exit;
}

$entryId = intval($input['entry_id']);

try {
    $db = getDBConnection();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Rate limiting: Max 3 resends per entry per hour
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM otps 
        WHERE entry_id = ? 
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$entryId]);
    $otpCount = $stmt->fetch();
    
    if ($otpCount['count'] >= 5) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Maximum OTP requests reached. Please try again later.']);
        exit;
    }
    
    // Rate limiting by IP
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM otp_attempts 
        WHERE ip_address = ? 
        AND attempt_type = 'generate' 
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$ipAddress]);
    $ipAttempts = $stmt->fetch();
    
    if ($ipAttempts['count'] >= 10) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many requests from your IP. Please try again later.']);
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
            'success' => false,
            'message' => 'Entry already verified. No need to resend OTP.'
        ]);
        exit;
    }
    
    // Invalidate old OTPs
    $stmt = $db->prepare("UPDATE otps SET is_used = 1 WHERE entry_id = ? AND is_used = 0");
    $stmt->execute([$entryId]);
    
    // Generate new 6-digit OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Set OTP expiration (10 minutes)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Store new OTP
    $stmt = $db->prepare("INSERT INTO otps (entry_id, otp_code, otp_type, expires_at) VALUES (?, ?, 'both', ?)");
    $stmt->execute([$entryId, $otp, $expiresAt]);
    
    // Send OTP via WhatsApp
    $whatsappMessage = getOTPSMSMessage($otp);
    $whatsappResult = sendSMS($entry['whatsapp'], $whatsappMessage); // Using SMS function for WhatsApp
    
    // Log WhatsApp
    $stmt = $db->prepare("INSERT INTO notification_logs (entry_id, notification_type, recipient, message, status, response) VALUES (?, 'whatsapp', ?, ?, ?, ?)");
    $stmt->execute([
        $entryId,
        $entry['whatsapp'],
        $whatsappMessage,
        $whatsappResult['success'] ? 'sent' : 'failed',
        json_encode($whatsappResult)
    ]);
    
    // Send OTP via SMS
    $smsMessage = getOTPSMSMessage($otp);
    $smsResult = sendSMS($entry['phone'], $smsMessage);
    
    // Log SMS
    $stmt = $db->prepare("INSERT INTO notification_logs (entry_id, notification_type, recipient, message, status, response) VALUES (?, 'sms', ?, ?, ?, ?)");
    $stmt->execute([
        $entryId,
        $entry['phone'],
        $smsMessage,
        $smsResult['success'] ? 'sent' : 'failed',
        json_encode($smsResult)
    ]);
    
    // Log attempt
    $stmt = $db->prepare("INSERT INTO otp_attempts (entry_id, ip_address, attempt_type, is_successful) VALUES (?, ?, 'generate', 1)");
    $stmt->execute([$entryId, $ipAddress]);
    
    echo json_encode([
        'success' => true,
        'message' => 'New OTP sent successfully',
        'data' => [
            'whatsapp_sent' => $whatsappResult['success'],
            'sms_sent' => $smsResult['success'],
            'expires_in' => 600
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Resend OTP Error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again.'
    ]);
}
