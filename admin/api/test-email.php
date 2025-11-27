<?php
/**
 * Test Email API
 * Sends a test email to verify configuration
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
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Valid email address required']);
    exit;
}

// Load mail configuration
$mailConfigFile = __DIR__ . '/../../api/config/mail.php';
$config = file_exists($mailConfigFile) ? require $mailConfigFile : ['service' => 'smtp'];

$service = $config['service'] ?? 'smtp';
$fromEmail = $config['from_email'] ?? 'noreply@localhost';
$fromName = $config['from_name'] ?? 'LOTD';

$subject = 'LOTD Test Email';
$message = "This is a test email from your LOTD Admin Panel.\n\nIf you received this email, your email configuration is working correctly!\n\nService: " . strtoupper($service) . "\nTime: " . date('Y-m-d H:i:s');

try {
    $sent = false;
    
    switch ($service) {
        case 'sendgrid':
            $sent = sendViaSendGrid($config, $email, $fromEmail, $fromName, $subject, $message);
            break;
            
        case 'mailgun':
            $sent = sendViaMailgun($config, $email, $fromEmail, $fromName, $subject, $message);
            break;
            
        case 'smtp':
        default:
            $sent = sendViaSMTP($config, $email, $fromEmail, $fromName, $subject, $message);
            break;
    }
    
    if ($sent) {
        echo json_encode(['success' => true, 'message' => "Test email sent to $email"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send email. Check your configuration.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function sendViaSMTP($config, $to, $fromEmail, $fromName, $subject, $message) {
    $smtp = $config['smtp'] ?? [];
    
    // Use PHP mail() as fallback for simple SMTP
    $headers = "From: $fromName <$fromEmail>\r\n";
    $headers .= "Reply-To: $fromEmail\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

function sendViaSendGrid($config, $to, $fromEmail, $fromName, $subject, $message) {
    $apiKey = $config['sendgrid']['api_key'] ?? '';
    
    if (empty($apiKey)) {
        throw new Exception('SendGrid API key not configured');
    }
    
    $data = [
        'personalizations' => [['to' => [['email' => $to]]]],
        'from' => ['email' => $fromEmail, 'name' => $fromName],
        'subject' => $subject,
        'content' => [['type' => 'text/plain', 'value' => $message]]
    ];
    
    $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($data)
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode >= 200 && $httpCode < 300;
}

function sendViaMailgun($config, $to, $fromEmail, $fromName, $subject, $message) {
    $apiKey = $config['mailgun']['api_key'] ?? '';
    $domain = $config['mailgun']['domain'] ?? '';
    
    if (empty($apiKey) || empty($domain)) {
        throw new Exception('Mailgun credentials not configured');
    }
    
    $ch = curl_init("https://api.mailgun.net/v3/$domain/messages");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_USERPWD => 'api:' . $apiKey,
        CURLOPT_POSTFIELDS => [
            'from' => "$fromName <$fromEmail>",
            'to' => $to,
            'subject' => $subject,
            'text' => $message
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode >= 200 && $httpCode < 300;
}
