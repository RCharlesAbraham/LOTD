<?php
/**
 * SMS Helper Functions
 * Provides SMS sending functionality for OTP delivery
 */

require_once __DIR__ . '/Fast2SMSService.php';

/**
 * Send SMS using configured service
 * 
 * @param string $phone Phone number
 * @param string $message SMS message
 * @return array Result with success status and message
 */
function sendSMS($phone, $message) {
    $configFile = __DIR__ . '/config/sms.php';
    
    if (!file_exists($configFile)) {
        return [
            'success' => false,
            'message' => 'SMS configuration file not found'
        ];
    }
    
    $config = require $configFile;
    $service = $config['service'] ?? 'log';
    
    switch ($service) {
        case 'fast2sms':
            return sendViaFast2SMS($config, $phone, $message);
            
        case 'twilio':
            return sendViaTwilio($config, $phone, $message);
            
        case 'nexmo':
            return sendViaNexmo($config, $phone, $message);
            
        case 'msg91':
            return sendViaMSG91($config, $phone, $message);
            
        case 'log':
        default:
            return logSMS($phone, $message);
    }
}

/**
 * Send SMS via Fast2SMS
 */
function sendViaFast2SMS($config, $phone, $message) {
    if (!isset($config['fast2sms'])) {
        return [
            'success' => false,
            'message' => 'Fast2SMS not configured'
        ];
    }
    
    $fast2sms = new Fast2SMSService($config['fast2sms']);
    return $fast2sms->sendSMS($phone, $message);
}

/**
 * Send SMS via Twilio
 */
function sendViaTwilio($config, $phone, $message) {
    if (!isset($config['twilio'])) {
        return [
            'success' => false,
            'message' => 'Twilio not configured'
        ];
    }
    
    $twilio = $config['twilio'];
    $sid = $twilio['account_sid'] ?? '';
    $token = $twilio['auth_token'] ?? '';
    $from = $twilio['from_number'] ?? '';
    
    if (empty($sid) || empty($token) || empty($from)) {
        return [
            'success' => false,
            'message' => 'Twilio credentials incomplete'
        ];
    }
    
    try {
        $ch = curl_init("https://api.twilio.com/2010-04-01/Accounts/$sid/Messages.json");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_USERPWD => "$sid:$token",
            CURLOPT_POSTFIELDS => http_build_query([
                'From' => $from,
                'To' => $phone,
                'Body' => $message
            ])
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'message' => 'SMS sent via Twilio'
            ];
        } else {
            $result = json_decode($response, true);
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Twilio error'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Twilio exception: ' . $e->getMessage()
        ];
    }
}

/**
 * Send SMS via Nexmo/Vonage
 */
function sendViaNexmo($config, $phone, $message) {
    if (!isset($config['nexmo'])) {
        return [
            'success' => false,
            'message' => 'Nexmo not configured'
        ];
    }
    
    $nexmo = $config['nexmo'];
    $key = $nexmo['api_key'] ?? '';
    $secret = $nexmo['api_secret'] ?? '';
    $from = $nexmo['from'] ?? 'LOTD';
    
    if (empty($key) || empty($secret)) {
        return [
            'success' => false,
            'message' => 'Nexmo credentials incomplete'
        ];
    }
    
    try {
        $ch = curl_init('https://rest.nexmo.com/sms/json');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'api_key' => $key,
                'api_secret' => $secret,
                'from' => $from,
                'to' => $phone,
                'text' => $message
            ])
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        if (isset($data['messages'][0]['status']) && $data['messages'][0]['status'] == '0') {
            return [
                'success' => true,
                'message' => 'SMS sent via Nexmo'
            ];
        } else {
            return [
                'success' => false,
                'message' => $data['messages'][0]['error-text'] ?? 'Nexmo error'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Nexmo exception: ' . $e->getMessage()
        ];
    }
}

/**
 * Send SMS via MSG91
 */
function sendViaMSG91($config, $phone, $message) {
    if (!isset($config['msg91'])) {
        return [
            'success' => false,
            'message' => 'MSG91 not configured'
        ];
    }
    
    $msg91 = $config['msg91'];
    $authKey = $msg91['auth_key'] ?? '';
    $senderId = $msg91['sender_id'] ?? '';
    
    if (empty($authKey)) {
        return [
            'success' => false,
            'message' => 'MSG91 auth key missing'
        ];
    }
    
    try {
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
                'mobiles' => $phone,
                'message' => $message
            ])
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'message' => 'SMS sent via MSG91'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'MSG91 error'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'MSG91 exception: ' . $e->getMessage()
        ];
    }
}

/**
 * Log SMS to file (testing mode)
 */
function logSMS($phone, $message) {
    $logFile = __DIR__ . '/logs/sms_log.txt';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logEntry = "[" . date('Y-m-d H:i:s') . "] To: $phone | Message: $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    return [
        'success' => true,
        'message' => 'SMS logged successfully (test mode)'
    ];
}

/**
 * Get OTP SMS message template
 * 
 * @param string $otp OTP code
 * @return string Formatted message
 */
function getOTPSMSMessage($otp) {
    return "Your LOTD verification code is: $otp. Valid for 10 minutes. Do not share this code.";
}
