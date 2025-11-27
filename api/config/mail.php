<?php
/**
 * Email Configuration
 * 
 * Configure your email service settings here
 * Supports: SMTP, SendGrid, Mailgun
 */

// Email Service Type: 'smtp', 'sendgrid', 'mailgun'
define('MAIL_SERVICE', 'smtp');

// SMTP Settings (for local testing with Laragon's built-in mail)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 25);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_FROM_EMAIL', 'noreply@lotd.local');
define('SMTP_FROM_NAME', 'LOTD Verification');

// SendGrid Settings (for production)
define('SENDGRID_API_KEY', 'your-sendgrid-api-key-here');

// Mailgun Settings (for production)
define('MAILGUN_API_KEY', 'your-mailgun-api-key-here');
define('MAILGUN_DOMAIN', 'your-domain.com');

/**
 * Send email using configured service
 */
function sendEmail($to, $subject, $htmlBody, $textBody = '') {
    switch (MAIL_SERVICE) {
        case 'sendgrid':
            return sendEmailViaSendGrid($to, $subject, $htmlBody, $textBody);
        case 'mailgun':
            return sendEmailViaMailgun($to, $subject, $htmlBody, $textBody);
        case 'smtp':
        default:
            return sendEmailViaSMTP($to, $subject, $htmlBody, $textBody);
    }
}

/**
 * Send email via PHP mail() / SMTP
 */
function sendEmailViaSMTP($to, $subject, $htmlBody, $textBody = '') {
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>',
        'Reply-To: ' . SMTP_FROM_EMAIL,
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $result = mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    
    return [
        'success' => $result,
        'message' => $result ? 'Email sent successfully' : 'Failed to send email'
    ];
}

/**
 * Send email via SendGrid API
 */
function sendEmailViaSendGrid($to, $subject, $htmlBody, $textBody = '') {
    $data = [
        'personalizations' => [
            [
                'to' => [['email' => $to]],
                'subject' => $subject
            ]
        ],
        'from' => [
            'email' => SMTP_FROM_EMAIL,
            'name' => SMTP_FROM_NAME
        ],
        'content' => [
            ['type' => 'text/html', 'value' => $htmlBody]
        ]
    ];
    
    if (!empty($textBody)) {
        array_unshift($data['content'], ['type' => 'text/plain', 'value' => $textBody]);
    }
    
    $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . SENDGRID_API_KEY,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'message' => $httpCode >= 200 && $httpCode < 300 ? 'Email sent successfully' : 'Failed to send email'
    ];
}

/**
 * Send email via Mailgun API
 */
function sendEmailViaMailgun($to, $subject, $htmlBody, $textBody = '') {
    $data = [
        'from' => SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>',
        'to' => $to,
        'subject' => $subject,
        'html' => $htmlBody
    ];
    
    if (!empty($textBody)) {
        $data['text'] = $textBody;
    }
    
    $ch = curl_init('https://api.mailgun.net/v3/' . MAILGUN_DOMAIN . '/messages');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, 'api:' . MAILGUN_API_KEY);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'success' => $httpCode >= 200 && $httpCode < 300,
        'message' => $httpCode >= 200 && $httpCode < 300 ? 'Email sent successfully' : 'Failed to send email'
    ];
}

/**
 * Generate OTP email template
 */
function getOTPEmailTemplate($name, $otp) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
            <tr>
                <td align="center">
                    <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <!-- Header -->
                        <tr>
                            <td style="background-color: #e1b168; padding: 30px; text-align: center;">
                                <h1 style="color: #ffffff; margin: 0; font-size: 24px;">LOTD Verification</h1>
                            </td>
                        </tr>
                        <!-- Body -->
                        <tr>
                            <td style="padding: 40px 30px;">
                                <h2 style="color: #333333; margin: 0 0 20px 0;">Hello ' . htmlspecialchars($name) . ',</h2>
                                <p style="color: #666666; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                    Your One-Time Password (OTP) for verification is:
                                </p>
                                <div style="background-color: #f8f8f8; border: 2px dashed #e1b168; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0;">
                                    <span style="font-size: 36px; font-weight: bold; color: #e1b168; letter-spacing: 8px;">' . $otp . '</span>
                                </div>
                                <p style="color: #666666; font-size: 14px; line-height: 1.6; margin: 20px 0 0 0;">
                                    This OTP is valid for <strong>10 minutes</strong>. Please do not share this code with anyone.
                                </p>
                                <p style="color: #999999; font-size: 12px; line-height: 1.6; margin: 20px 0 0 0;">
                                    If you did not request this OTP, please ignore this email.
                                </p>
                            </td>
                        </tr>
                        <!-- Footer -->
                        <tr>
                            <td style="background-color: #f8f8f8; padding: 20px 30px; text-align: center; border-top: 1px solid #eeeeee;">
                                <p style="color: #999999; font-size: 12px; margin: 0;">
                                    &copy; ' . date('Y') . ' LOTD. All rights reserved.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';
}
