<?php
/**
 * Test SMS API
 * Sends a test SMS to verify configuration
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
$phone = preg_replace('/[^0-9+]/', '', $input['phone'] ?? '');

if (strlen($phone) < 10) {
    echo json_encode(['success' => false, 'message' => 'Valid phone number required']);
    exit;
}

// Load SMS configuration
$smsConfigFile = __DIR__ . '/../../api/config/sms.php';
$config = file_exists($smsConfigFile) ? require $smsConfigFile : ['service' => 'log'];

$service = $config['service'] ?? 'log';
$message = "LOTD Test: Your SMS configuration is working! Time: " . date('H:i:s');

try {
    $sent = false;
    
    switch ($service) {
        case 'twilio':
            $sent = sendViaTwilio($config, $phone, $message);
            break;
            
        case 'nexmo':
            $sent = sendViaNexmo($config, $phone, $message);
            break;
            
        case 'msg91':
            $sent = sendViaMSG91($config, $phone, $message);
            break;
            
        case 'log':
        default:
            // Log mode - save to file
            $logFile = __DIR__ . '/../../api/logs/sms_test.log';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) mkdir($logDir, 0755, true);
            
            $logEntry = "[" . date('Y-m-d H:i:s') . "] TEST SMS to $phone: $message\n";
            file_put_contents($logFile, $logEntry, FILE_APPEND);
            $sent = true;
            break;
    }
    
    if ($sent) {
        $modeMsg = $service === 'log' ? " (Log Mode - check api/logs/sms_test.log)" : "";
        echo json_encode(['success' => true, 'message' => "Test SMS sent to $phone$modeMsg"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send SMS. Check your configuration.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function sendViaTwilio($config, $to, $message) {
    $twilio = $config['twilio'] ?? [];
    $sid = $twilio['account_sid'] ?? '';
    $token = $twilio['auth_token'] ?? '';
    $from = $twilio['from_number'] ?? '';
    
    if (empty($sid) || empty($token) || empty($from)) {
        throw new Exception('Twilio credentials not configured');
    }
    
    $ch = curl_init("https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_USERPWD => "$sid:$token",
        CURLOPT_POSTFIELDS => http_build_query([
            'From' => $from,
            'To' => $to,
            'Body' => $message
        ])
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode >= 200 && $httpCode < 300;
}

function sendViaNexmo($config, $to, $message) {
    $nexmo = $config['nexmo'] ?? [];
    $key = $nexmo['api_key'] ?? '';
    $secret = $nexmo['api_secret'] ?? '';
    $from = $nexmo['from'] ?? 'LOTD';
    
    if (empty($key) || empty($secret)) {
        throw new Exception('Nexmo credentials not configured');
    }
    
    $ch = curl_init('https://rest.nexmo.com/sms/json');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'api_key' => $key,
            'api_secret' => $secret,
            'from' => $from,
            'to' => $to,
            'text' => $message
        ])
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    return isset($data['messages'][0]['status']) && $data['messages'][0]['status'] == '0';
}

function sendViaMSG91($config, $to, $message) {
    $msg91 = $config['msg91'] ?? [];
    $authKey = $msg91['auth_key'] ?? '';
    $senderId = $msg91['sender_id'] ?? '';
    
    if (empty($authKey)) {
        throw new Exception('MSG91 auth key not configured');
    }
    
    $ch = curl_init('https://api.msg91.com/api/v5/flow/');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'authkey: ' . $authKey,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'sender' => $senderId,
            'mobiles' => $to,
            'message' => $message
        ])
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode >= 200 && $httpCode < 300;
}
