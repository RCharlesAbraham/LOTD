<?php
/**
 * Settings API
 * GET - Load all settings
 * POST - Save settings
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Check if database config exists
$configFile = __DIR__ . '/../../api/config/database.php';
if (file_exists($configFile)) {
    require_once $configFile;
}

// Sensitive keys that should be masked when returned
$sensitiveKeys = ['smtp_pass', 'sendgrid_api_key', 'mailgun_api_key', 'twilio_token', 'nexmo_secret', 'msg91_auth_key'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Load settings
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        
        while ($row = $stmt->fetch()) {
            $key = $row['setting_key'];
            $value = $row['setting_value'];
            
            // Mask sensitive values
            if (in_array($key, $sensitiveKeys) && !empty($value)) {
                $settings[$key] = true; // Just indicate it exists
            } else {
                $settings[$key] = $value;
            }
        }
        
        // Add PHP version
        $settings['php_version'] = PHP_VERSION;
        
        echo json_encode(['success' => true, 'data' => $settings]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Could not load settings']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save settings
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input)) {
        echo json_encode(['success' => false, 'message' => 'No settings provided']);
        exit;
    }

    try {
        $pdo = getDBConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value, updated_at) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ");
        
        foreach ($input as $key => $value) {
            // Sanitize key name
            $key = preg_replace('/[^a-z0-9_]/', '', strtolower($key));
            if (!empty($key)) {
                $stmt->execute([$key, $value]);
            }
        }
        
        // Also update config files if email/sms settings changed
        updateConfigFiles($input);
        
        echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Could not save settings: ' . $e->getMessage()]);
    }
    exit;
}

function updateConfigFiles($settings) {
    $configDir = __DIR__ . '/../../api/config';
    
    // Update mail config if email settings changed
    if (isset($settings['email_service'])) {
        $mailConfig = generateMailConfig($settings);
        file_put_contents($configDir . '/mail.php', $mailConfig);
    }
    
    // Update SMS config if SMS settings changed
    if (isset($settings['sms_service'])) {
        $smsConfig = generateSmsConfig($settings);
        file_put_contents($configDir . '/sms.php', $smsConfig);
    }
}

function generateMailConfig($s) {
    $service = $s['email_service'] ?? 'smtp';
    $fromEmail = $s['from_email'] ?? 'noreply@example.com';
    $fromName = $s['from_name'] ?? 'LOTD';
    
    $config = "<?php
/**
 * Email Configuration
 * Auto-generated from Admin Settings
 */

return [
    'service' => '$service',
    'from_email' => '$fromEmail',
    'from_name' => '$fromName',
";
    
    if ($service === 'smtp') {
        $host = $s['smtp_host'] ?? 'localhost';
        $port = $s['smtp_port'] ?? '587';
        $user = $s['smtp_user'] ?? '';
        $pass = $s['smtp_pass'] ?? '';
        $encryption = $s['smtp_encryption'] ?? 'tls';
        
        $config .= "
    'smtp' => [
        'host' => '$host',
        'port' => $port,
        'username' => '$user',
        'password' => '$pass',
        'encryption' => '$encryption'
    ],
";
    } elseif ($service === 'sendgrid') {
        $apiKey = $s['sendgrid_api_key'] ?? '';
        $config .= "
    'sendgrid' => [
        'api_key' => '$apiKey'
    ],
";
    } elseif ($service === 'mailgun') {
        $apiKey = $s['mailgun_api_key'] ?? '';
        $domain = $s['mailgun_domain'] ?? '';
        $config .= "
    'mailgun' => [
        'api_key' => '$apiKey',
        'domain' => '$domain'
    ],
";
    }
    
    $config .= "];
";
    
    return $config;
}

function generateSmsConfig($s) {
    $service = $s['sms_service'] ?? 'log';
    
    $config = "<?php
/**
 * SMS Configuration
 * Auto-generated from Admin Settings
 */

return [
    'service' => '$service',
";
    
    if ($service === 'twilio') {
        $sid = $s['twilio_sid'] ?? '';
        $token = $s['twilio_token'] ?? '';
        $phone = $s['twilio_phone'] ?? '';
        
        $config .= "
    'twilio' => [
        'account_sid' => '$sid',
        'auth_token' => '$token',
        'from_number' => '$phone'
    ],
";
    } elseif ($service === 'nexmo') {
        $key = $s['nexmo_key'] ?? '';
        $secret = $s['nexmo_secret'] ?? '';
        $from = $s['nexmo_from'] ?? 'LOTD';
        
        $config .= "
    'nexmo' => [
        'api_key' => '$key',
        'api_secret' => '$secret',
        'from' => '$from'
    ],
";
    } elseif ($service === 'msg91') {
        $authKey = $s['msg91_auth_key'] ?? '';
        $senderId = $s['msg91_sender_id'] ?? '';
        $templateId = $s['msg91_template_id'] ?? '';
        
        $config .= "
    'msg91' => [
        'auth_key' => '$authKey',
        'sender_id' => '$senderId',
        'template_id' => '$templateId'
    ],
";
    }
    
    $config .= "];
";
    
    return $config;
}
