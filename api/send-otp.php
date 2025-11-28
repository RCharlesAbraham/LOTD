<?php
/**
 * Send OTP API Endpoint
 * 
 * POST /api/send-otp.php
 * 
 * Request Body:
 * {
 *   "name": "John Doe",
 *   "whatsapp": "1234567890",
 *   "phone": "1234567890"
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
if (empty($input['name']) || empty($input['whatsapp']) || empty($input['phone'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name, whatsapp, and phone are required']);
    exit;
}

$name = trim($input['name']);
$whatsapp = preg_replace('/[^0-9+]/', '', trim($input['whatsapp']));
$phone = preg_replace('/[^0-9+]/', '', trim($input['phone']));

// Validate whatsapp (at least 10 digits)
if (strlen(preg_replace('/[^0-9]/', '', $whatsapp)) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid WhatsApp number']);
    exit;
}

// Validate phone (at least 10 digits)
if (strlen(preg_replace('/[^0-9]/', '', $phone)) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid phone number']);
    exit;
}

try {
    $db = getDBConnection();
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Rate limiting: Check if too many OTP requests from this IP
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM otp_attempts 
        WHERE ip_address = ? 
        AND attempt_type = 'generate' 
        AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$ipAddress]);
    $attempts = $stmt->fetch();
    
    if ($attempts['count'] >= 10) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many OTP requests. Please try again later.']);
        exit;
    }
    
    // Check if entry with this whatsapp/phone already exists and is not verified
    $stmt = $db->prepare("SELECT id, entry_number FROM entries WHERE whatsapp = ? OR phone = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$whatsapp, $phone]);
    $existingEntry = $stmt->fetch();
    
    if ($existingEntry) {
        $entryId = $existingEntry['id'];
        $entryNumber = $existingEntry['entry_number'];
        
        // Update existing entry
        $stmt = $db->prepare("UPDATE entries SET name = ?, whatsapp = ?, phone = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name, $whatsapp, $phone, $entryId]);
        
        // Invalidate old OTPs
        $stmt = $db->prepare("UPDATE otps SET is_used = 1 WHERE entry_id = ? AND is_used = 0");
        $stmt->execute([$entryId]);
    } else {
        // Generate unique entry number
        $entryNumber = 'LOTD' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        
        // Create new entry
        $stmt = $db->prepare("INSERT INTO entries (entry_number, name, whatsapp, phone) VALUES (?, ?, ?, ?)");
        $stmt->execute([$entryNumber, $name, $whatsapp, $phone]);
        $entryId = $db->lastInsertId();
    }
    
    // Generate 6-digit OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    
    // Set OTP expiration (10 minutes)
    $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Store OTP in database
    $stmt = $db->prepare("INSERT INTO otps (entry_id, otp_code, otp_type, expires_at) VALUES (?, ?, 'both', ?)");
    $stmt->execute([$entryId, $otp, $expiresAt]);
    
    // Send OTP via WhatsApp
    $whatsappMessage = getOTPSMSMessage($otp);
    $whatsappResult = sendSMS($whatsapp, $whatsappMessage); // Using SMS function for WhatsApp
    
    // Log WhatsApp notification
    $stmt = $db->prepare("INSERT INTO notification_logs (entry_id, notification_type, recipient, message, status, response) VALUES (?, 'whatsapp', ?, ?, ?, ?)");
    $stmt->execute([
        $entryId, 
        $whatsapp, 
        $whatsappMessage, 
        $whatsappResult['success'] ? 'sent' : 'failed',
        json_encode($whatsappResult)
    ]);
    
    // Send OTP via SMS
    $smsMessage = getOTPSMSMessage($otp);
    $smsResult = sendSMS($phone, $smsMessage);
    
    // Log SMS notification
    $stmt = $db->prepare("INSERT INTO notification_logs (entry_id, notification_type, recipient, message, status, response) VALUES (?, 'sms', ?, ?, ?, ?)");
    $stmt->execute([
        $entryId,
        $phone,
        $smsMessage,
        $smsResult['success'] ? 'sent' : 'failed',
        json_encode($smsResult)
    ]);
    
    // Log OTP generation attempt
    $stmt = $db->prepare("INSERT INTO otp_attempts (entry_id, ip_address, attempt_type, is_successful) VALUES (?, ?, 'generate', 1)");
    $stmt->execute([$entryId, $ipAddress]);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'OTP sent successfully to your WhatsApp and phone',
        'data' => [
            'entry_id' => $entryId,
            'entry_number' => $entryNumber,
            'whatsapp_sent' => $whatsappResult['success'],
            'sms_sent' => $smsResult['success'],
            'expires_in' => 600 // seconds
        ]
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log('Send OTP Error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while sending OTP. Please try again.'
    ]);
}
