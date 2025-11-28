<?php
/**
 * Database Setup Script
 * 
 * Run this script once to create the database and tables
 * Access via: http://localhost/LOTD/api/setup.php
 */

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = ''; // Laragon default

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>LOTD Database Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: #10b981; background: #ecfdf5; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #ef4444; background: #fef2f2; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #3b82f6; background: #eff6ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        pre { background: #f3f4f6; padding: 15px; border-radius: 5px; overflow-x: auto; }
        h1 { color: #e1b168; }
        .btn { background: #e1b168; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <h1>🗄️ LOTD Database Setup</h1>
    
<?php
try {
    // Connect without database
    $pdo = new PDO("mysql:host=$host", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo '<div class="success">✅ Connected to MySQL server successfully!</div>';
    
    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS lotd_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo '<div class="success">✅ Database "lotd_db" created or already exists!</div>';
    
    // Select database
    $pdo->exec("USE lotd_db");
    
    // Create entries table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS entries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            entry_number VARCHAR(20) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            whatsapp VARCHAR(20) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            is_verified TINYINT(1) DEFAULT 0,
            verified_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX idx_whatsapp (whatsapp),
            INDEX idx_phone (phone),
            INDEX idx_entry_number (entry_number),
            INDEX idx_verified (is_verified)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo '<div class="success">✅ Table "entries" created!</div>';
    
    // Create otps table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS otps (
            id INT AUTO_INCREMENT PRIMARY KEY,
            entry_id INT NOT NULL,
            otp_code VARCHAR(6) NOT NULL,
            otp_type ENUM('whatsapp', 'phone', 'both') DEFAULT 'both',
            is_used TINYINT(1) DEFAULT 0,
            attempts INT DEFAULT 0,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (entry_id) REFERENCES entries(id) ON DELETE CASCADE,
            INDEX idx_otp_code (otp_code),
            INDEX idx_entry_id (entry_id),
            INDEX idx_expires_at (expires_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo '<div class="success">✅ Table "otps" created!</div>';
    
    // Create otp_attempts table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS otp_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            entry_id INT NULL,
            ip_address VARCHAR(45) NOT NULL,
            attempt_type ENUM('generate', 'verify') NOT NULL,
            is_successful TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (entry_id) REFERENCES entries(id) ON DELETE SET NULL,
            INDEX idx_ip_address (ip_address),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo '<div class="success">✅ Table "otp_attempts" created!</div>';
    
    // Create notification_logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notification_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            entry_id INT NOT NULL,
            notification_type ENUM('whatsapp', 'sms') NOT NULL,
            recipient VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NULL,
            message TEXT NULL,
            status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
            response TEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (entry_id) REFERENCES entries(id) ON DELETE CASCADE,
            INDEX idx_entry_id (entry_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo '<div class="success">✅ Table "notification_logs" created!</div>';
    
    echo '<br><div class="success"><strong>🎉 Database setup completed successfully!</strong></div>';
    
    echo '<h2>📋 Database Structure</h2>';
    echo '<pre>';
    echo "Database: lotd_db\n\n";
    echo "Tables:\n";
    echo "  ├── entries (stores user information)\n";
    echo "  ├── otps (stores OTP codes)\n";
    echo "  ├── otp_attempts (tracks verification attempts)\n";
    echo "  └── notification_logs (whatsapp/SMS logs)\n";
    echo '</pre>';
    
    echo '<h2>🔗 API Endpoints</h2>';
    echo '<pre>';
    echo "POST /api/send-otp.php      - Generate and send OTP\n";
    echo "POST /api/verify-otp.php    - Verify OTP code\n";
    echo "POST /api/resend-otp.php    - Resend OTP\n";
    echo '</pre>';
    
    echo '<br><a href="../index.html" class="btn">← Go to Entry Form</a>';
    
} catch (PDOException $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<div class="info">💡 Make sure MySQL is running in Laragon and the credentials are correct.</div>';
}
?>

</body>
</html>
