<?php
/**
 * WhatsApp Service
 * Sends WhatsApp messages with QR codes containing registration data
 */

class WhatsAppService {
    private $config;
    private $service;

    public function __construct() {
        $configFile = __DIR__ . '/config/whatsapp.php';
        if (file_exists($configFile)) {
            $this->config = require $configFile;
            $this->service = $this->config['service'] ?? 'disabled';
        } else {
            $this->service = 'disabled';
        }
    }

    /**
     * Send WhatsApp message with QR code
     */
    public function sendRegistrationSuccess($phone, $userData) {
        if ($this->service === 'disabled') {
            return ['success' => false, 'message' => 'WhatsApp service is disabled'];
        }

        // Generate QR code
        $qrCodeUrl = $this->generateQRCode($userData);

        // Format phone number
        $phone = $this->formatPhoneNumber($phone);

        // Send based on configured service
        switch ($this->service) {
            case 'twilio':
                return $this->sendViaTwilio($phone, $userData, $qrCodeUrl);
            case 'meta':
                return $this->sendViaMeta($phone, $userData, $qrCodeUrl);
            case 'ultramsg':
                return $this->sendViaUltraMsg($phone, $userData, $qrCodeUrl);
            case 'wati':
                return $this->sendViaWati($phone, $userData, $qrCodeUrl);
            default:
                return $this->logMessage($phone, $userData, $qrCodeUrl);
        }
    }

    /**
     * Generate QR Code containing user registration data
     */
    private function generateQRCode($userData) {
        // Create QR code data string
        $qrData = json_encode([
            'entry_number' => $userData['entry_number'],
            'name' => $userData['name'],
            'email' => $userData['email'],
            'phone' => $userData['phone'],
            'verified_at' => $userData['verified_at'],
            'app' => 'LOTD'
        ]);

        // Use QR Server API for QR code generation (free, no API key needed)
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qrData);

        return $qrCodeUrl;
    }

    /**
     * Format phone number for WhatsApp
     */
    private function formatPhoneNumber($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if not present (assuming India +91)
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }
        
        return $phone;
    }

    /**
     * Send via Twilio WhatsApp
     */
    private function sendViaTwilio($phone, $userData, $qrCodeUrl) {
        $config = $this->config['twilio'];
        
        $message = $this->formatMessage($userData);
        
        $url = 'https://api.twilio.com/2010-04-01/Accounts/' . $config['account_sid'] . '/Messages.json';
        
        $data = [
            'From' => $config['from_number'],
            'To' => 'whatsapp:+' . $phone,
            'Body' => $message,
            'MediaUrl' => $qrCodeUrl
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_USERPWD, $config['account_sid'] . ':' . $config['auth_token']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'message' => 'WhatsApp sent successfully', 'sid' => $result['sid'] ?? ''];
        }
        
        return ['success' => false, 'message' => $result['message'] ?? 'Failed to send WhatsApp'];
    }

    /**
     * Send via Meta (Facebook) WhatsApp Business API
     */
    private function sendViaMeta($phone, $userData, $qrCodeUrl) {
        $config = $this->config['meta'];
        
        $url = 'https://graph.facebook.com/' . $config['api_version'] . '/' . $config['phone_number_id'] . '/messages';
        
        // First send text message
        $textData = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'text',
            'text' => ['body' => $this->formatMessage($userData)]
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($textData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $config['access_token'],
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        // Then send QR code image
        $imageData = [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'image',
            'image' => ['link' => $qrCodeUrl, 'caption' => 'Your Registration QR Code']
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($imageData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $config['access_token'],
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'message' => 'WhatsApp sent successfully'];
        }
        
        return ['success' => false, 'message' => $result['error']['message'] ?? 'Failed to send WhatsApp'];
    }

    /**
     * Send via UltraMsg (Easy WhatsApp API)
     */
    private function sendViaUltraMsg($phone, $userData, $qrCodeUrl) {
        $config = $this->config['ultramsg'];
        
        $message = $this->formatMessage($userData);
        
        // Send text message
        $textUrl = 'https://api.ultramsg.com/' . $config['instance_id'] . '/messages/chat';
        
        $textData = [
            'token' => $config['token'],
            'to' => '+' . $phone,
            'body' => $message
        ];

        $ch = curl_init($textUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($textData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        // Send QR code image
        $imageUrl = 'https://api.ultramsg.com/' . $config['instance_id'] . '/messages/image';
        
        $imageData = [
            'token' => $config['token'],
            'to' => '+' . $phone,
            'image' => $qrCodeUrl,
            'caption' => 'Your Registration QR Code - Scan to verify'
        ];

        $ch = curl_init($imageUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($imageData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        
        if (isset($result['sent']) && $result['sent'] === 'true') {
            return ['success' => true, 'message' => 'WhatsApp sent successfully'];
        }
        
        return ['success' => false, 'message' => $result['error'] ?? 'Failed to send WhatsApp'];
    }

    /**
     * Send via WATI
     */
    private function sendViaWati($phone, $userData, $qrCodeUrl) {
        $config = $this->config['wati'];
        
        $message = $this->formatMessage($userData);
        
        // Send text message
        $url = $config['api_endpoint'] . '/api/v1/sendSessionMessage/' . $phone;
        
        $data = ['messageText' => $message];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $config['access_token'],
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);

        // Send image
        $imageUrl = $config['api_endpoint'] . '/api/v1/sendSessionFile/' . $phone;
        
        $imageData = [
            'url' => $qrCodeUrl,
            'caption' => 'Your Registration QR Code'
        ];

        $ch = curl_init($imageUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($imageData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $config['access_token'],
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'message' => 'WhatsApp sent successfully'];
        }
        
        return ['success' => false, 'message' => $result['message'] ?? 'Failed to send WhatsApp'];
    }

    /**
     * Log message (for testing/development)
     */
    private function logMessage($phone, $userData, $qrCodeUrl) {
        $logDir = __DIR__ . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/whatsapp_' . date('Y-m-d') . '.log';
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'phone' => $phone,
            'message' => $this->formatMessage($userData),
            'qr_code_url' => $qrCodeUrl,
            'user_data' => $userData
        ];
        
        file_put_contents($logFile, json_encode($logEntry, JSON_PRETTY_PRINT) . "\n---\n", FILE_APPEND);
        
        return ['success' => true, 'message' => 'WhatsApp logged successfully (test mode)', 'qr_code_url' => $qrCodeUrl];
    }

    /**
     * Format the success message
     */
    private function formatMessage($userData) {
        return "🎉 *Registration Successful!*\n\n" .
               "Hello *{$userData['name']}*,\n\n" .
               "Your registration has been verified successfully!\n\n" .
               "📋 *Registration Details:*\n" .
               "━━━━━━━━━━━━━━━━━\n" .
               "🔖 Entry Number: *{$userData['entry_number']}*\n" .
               "👤 Name: {$userData['name']}\n" .
               "📧 Email: {$userData['email']}\n" .
               "📱 Phone: {$userData['phone']}\n" .
               "✅ Verified: {$userData['verified_at']}\n" .
               "━━━━━━━━━━━━━━━━━\n\n" .
               "📎 *Your QR Code is attached below.*\n" .
               "Keep it safe for future reference.\n\n" .
               "Thank you for registering with LOTD! 🙏";
    }

    /**
     * Get QR code URL for user data
     */
    public function getQRCodeUrl($userData) {
        return $this->generateQRCode($userData);
    }
}
