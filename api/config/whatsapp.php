<?php
/**
 * WhatsApp Configuration
 * Configure your WhatsApp Business API credentials
 */

return [
    // WhatsApp Business API Provider
    // Options: 'disabled', 'twilio', 'meta', 'ultramsg', 'wati'
    'service' => 'ultramsg',

    // Twilio WhatsApp Configuration
    'twilio' => [
        'account_sid' => '',
        'auth_token' => '',
        'from_number' => '' // Format: whatsapp:+1234567890
    ],

    // Meta (Facebook) WhatsApp Business API
    'meta' => [
        'phone_number_id' => '',
        'access_token' => '',
        'api_version' => 'v18.0'
    ],

    // UltraMsg Configuration (Easy WhatsApp API)
    'ultramsg' => [
        'instance_id' => '',
        'token' => ''
    ],

    // WATI Configuration
    'wati' => [
        'api_endpoint' => '',
        'access_token' => ''
    ]
];
