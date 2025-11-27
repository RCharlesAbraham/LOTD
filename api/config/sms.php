<?php
/**
 * SMS Configuration
 * 
 * Configure your SMS service settings here
 * Supports: Twilio, Nexmo/Vonage, MSG91
 */

// SMS Service Type: 'twilio', 'nexmo', 'msg91', 'log' (for testing)
define('SMS_SERVICE', 'log'); // Use 'log' for testing - logs SMS to file

// Twilio Settings
define('TWILIO_ACCOUNT_SID', 'your-twilio-account-sid');
define('TWILIO_AUTH_TOKEN', 'your-twilio-auth-token');
define('TWILIO_FROM_NUMBER', '+1234567890');

// Nexmo/Vonage Settings
define('NEXMO_API_KEY', 'your-nexmo-api-key');
define('NEXMO_API_SECRET', 'your-nexmo-api-secret');
define('NEXMO_FROM', 'LOTD');

// MSG91 Settings (Popular in India)
define('MSG91_AUTH_KEY', 'your-msg91-auth-key');
define('MSG91_SENDER_ID', 'LOTDOT');
define('MSG91_TEMPLATE_ID', 'your-template-id');

/**
 * Send SMS using configured service
 */
function sendSMS($to, $message) {
    // Clean phone number
    $to = preg_replace('/[^0-9+]/', '', $to);
    
    switch (SMS_SERVICE) {
        case 'twilio':
            return sendSMSViaTwilio($to, $message);
        case 'nexmo':
            return sendSMSViaNexmo($to, $message);
        case 'msg91':
            return sendSMSViaMSG91($to, $message);
        case 'log':
        default:
            return logSMS($to, $message);
    }
}

/**
 * Log SMS to file (for testing)
 */
function logSMS($to, $message) {
    $logDir = __DIR__ . '/../../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'to' => $to,
        'message' => $message
    ];
    
    $logFile = $logDir . '/sms_log.txt';
    file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND);
    
    return [
        'success' => true,
        'message' => 'SMS logged successfully (test mode)'
    ];
}

/**
 * Send SMS via Twilio
 */
function sendSMSViaTwilio($to, $message) {
    $url = 'https://api.twilio.com/2010-04-01/Accounts/' . TWILIO_ACCOUNT_SID . '/Messages.json';
    
    $data = [
        'From' => TWILIO_FROM_NUMBER,
        'To' => $to,
        'Body' => $message
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, TWILIO_ACCOUNT_SID . ':' . TWILIO_AUTH_TOKEN);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'message' => $httpCode >= 200 && $httpCode < 300 ? 'SMS sent successfully' : ($result['message'] ?? 'Failed to send SMS')
    ];
}

/**
 * Send SMS via Nexmo/Vonage
 */
function sendSMSViaNexmo($to, $message) {
    $url = 'https://rest.nexmo.com/sms/json';
    
    $data = [
        'api_key' => NEXMO_API_KEY,
        'api_secret' => NEXMO_API_SECRET,
        'from' => NEXMO_FROM,
        'to' => $to,
        'text' => $message
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $result = json_decode($response, true);
    $success = isset($result['messages'][0]['status']) && $result['messages'][0]['status'] == '0';
    
    return [
        'success' => $success,
        'message' => $success ? 'SMS sent successfully' : ($result['messages'][0]['error-text'] ?? 'Failed to send SMS')
    ];
}

/**
 * Send SMS via MSG91
 */
function sendSMSViaMSG91($to, $message) {
    $url = 'https://api.msg91.com/api/v5/flow/';
    
    $data = [
        'template_id' => MSG91_TEMPLATE_ID,
        'sender' => MSG91_SENDER_ID,
        'mobiles' => $to,
        'OTP' => $message // Assuming OTP is the variable in template
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'authkey: ' . MSG91_AUTH_KEY,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'message' => $httpCode >= 200 && $httpCode < 300 ? 'SMS sent successfully' : 'Failed to send SMS'
    ];
}

/**
 * Generate OTP SMS message
 */
function getOTPSMSMessage($otp) {
    return "Your LOTD verification code is: {$otp}. Valid for 10 minutes. Do not share this code.";
}
