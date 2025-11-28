<?php
/**
 * Test WhatsApp API Endpoint
 * Sends a test WhatsApp message with sample QR code
 */

require_once __DIR__ . '/../WhatsAppService.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['phone'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Phone number is required']);
    exit;
}

$phone = $input['phone'];

// Create test user data
$testData = [
    'entry_number' => 'LOTD-TEST-' . date('His'),
    'name' => 'Test User',
    'email' => 'test@example.com',
    'phone' => $phone,
    'verified_at' => date('Y-m-d H:i:s')
];

try {
    $whatsappService = new WhatsAppService();
    $result = $whatsappService->sendRegistrationSuccess($phone, $testData);
    
    echo json_encode([
        'success' => $result['success'],
        'message' => $result['message'] ?? ($result['success'] ? 'Test WhatsApp message sent!' : 'Failed to send'),
        'qr_code_url' => $result['qr_code_url'] ?? $whatsappService->getQRCodeUrl($testData)
    ]);
} catch (Exception $e) {
    error_log('Test WhatsApp Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error sending WhatsApp: ' . $e->getMessage()
    ]);
}
