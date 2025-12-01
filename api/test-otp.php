<?php
/**
 * Test Send OTP Script
 * Quick test to debug the send-otp.php issues
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Testing Send OTP...</h2>";

try {
    echo "<p>1. Testing database connection...</p>";
    require_once __DIR__ . '/config/database.php';
    $db = getDBConnection();
    echo "<p style='color:green'>✓ Database connected</p>";
    
    echo "<p>2. Testing SMS helper...</p>";
    require_once __DIR__ . '/sms_helper.php';
    echo "<p style='color:green'>✓ SMS helper loaded</p>";
    
    echo "<p>3. Testing Fast2SMS configuration...</p>";
    $config = require __DIR__ . '/config/sms.php';
    echo "<pre>";
    print_r($config);
    echo "</pre>";
    
    echo "<p>4. Testing getOTPSMSMessage function...</p>";
    $message = getOTPSMSMessage('123456');
    echo "<p>Message: <code>$message</code></p>";
    
    echo "<p>5. Testing sendSMS function (log mode test)...</p>";
    $result = sendSMS('1234567890', 'Test message');
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if ($result['success']) {
        echo "<p style='color:green'>✓ All tests passed!</p>";
    } else {
        echo "<p style='color:orange'>⚠ SMS function returned: " . $result['message'] . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<h3>Try sending an actual OTP:</h3>";
echo "<p><a href='send-otp.php'>Test send-otp.php with sample data</a></p>";
