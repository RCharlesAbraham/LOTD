<?php
/**
 * Fast2SMS Service
 * Handles SMS sending via Fast2SMS API
 * Documentation: https://www.fast2sms.com/dashboard/dev-api
 */

class Fast2SMSService {
    private $apiKey;
    private $senderId;
    private $route;
    private $language;
    private $apiUrl = 'https://www.fast2sms.com/dev/bulkV2';

    public function __construct($config) {
        $this->apiKey = $config['api_key'] ?? '';
        $this->senderId = $config['sender_id'] ?? 'FSTSMS';
        $this->route = $config['route'] ?? 'dlt';
        $this->language = $config['language'] ?? 'english';
    }

    /**
     * Send SMS via Fast2SMS
     * 
     * @param string $phone Phone number (10 digits without country code for India)
     * @param string $message SMS message content
     * @return array Response with success status and message
     */
    public function sendSMS($phone, $message) {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => 'Fast2SMS API key not configured'
            ];
        }

        // Clean phone number - remove country code if present
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) > 10) {
            $phone = substr($phone, -10); // Get last 10 digits
        }

        // Validate phone number
        if (strlen($phone) != 10) {
            return [
                'success' => false,
                'message' => 'Invalid phone number format. Must be 10 digits.'
            ];
        }

        // Prepare request data
        $data = [
            'authorization' => $this->apiKey,
            'sender_id' => $this->senderId,
            'message' => $message,
            'language' => $this->language,
            'route' => $this->route,
            'numbers' => $phone,
        ];

        try {
            // Initialize cURL
            $ch = curl_init();
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $this->apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Cache-Control: no-cache'
                ],
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return [
                    'success' => false,
                    'message' => 'cURL Error: ' . $error,
                    'raw_response' => null
                ];
            }

            // Parse JSON response
            $result = json_decode($response, true);

            if ($httpCode == 200 && isset($result['return']) && $result['return'] == true) {
                return [
                    'success' => true,
                    'message' => 'SMS sent successfully via Fast2SMS',
                    'message_id' => $result['request_id'] ?? null,
                    'raw_response' => $result
                ];
            } else {
                $errorMessage = $result['message'] ?? 'Unknown error occurred';
                return [
                    'success' => false,
                    'message' => 'Fast2SMS Error: ' . $errorMessage,
                    'raw_response' => $result
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'raw_response' => null
            ];
        }
    }

    /**
     * Send OTP SMS (using OTP route for better delivery)
     * 
     * @param string $phone Phone number
     * @param string $otp OTP code
     * @return array Response
     */
    public function sendOTP($phone, $otp) {
        // Use predefined OTP template if route is 'otp'
        if ($this->route === 'otp') {
            $message = "Your OTP is: $otp. Valid for 10 minutes. Do not share this code.";
        } else {
            $message = "Your LOTD verification code is: $otp. Valid for 10 minutes. Do not share this code.";
        }
        
        return $this->sendSMS($phone, $message);
    }

    /**
     * Check account balance
     * 
     * @return array Balance information
     */
    public function getBalance() {
        if (empty($this->apiKey)) {
            return [
                'success' => false,
                'message' => 'Fast2SMS API key not configured'
            ];
        }

        try {
            $ch = curl_init();
            
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://www.fast2sms.com/dev/wallet',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'authorization: ' . $this->apiKey
                ],
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response, true);

            if (isset($result['wallet'])) {
                return [
                    'success' => true,
                    'balance' => $result['wallet'],
                    'message' => 'Balance retrieved successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Could not retrieve balance'
                ];
            }

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }
}
