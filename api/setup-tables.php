<?php
/**
 * Automatic Database Table Setup
 * Creates all required tables for LOTD
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    // Connect to database
    $host = 'localhost';
    $dbname = 'lotd_db';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO(
        "mysql:host=$host;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbname`");
    
    $tablesCreated = 0;
    
    // Create admin_users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `admin_users` (
          `id` int NOT NULL AUTO_INCREMENT,
          `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
          `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
          `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
          `is_active` tinyint(1) DEFAULT '1',
          `last_login` datetime DEFAULT NULL,
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `username` (`username`),
          UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $tablesCreated++;
    
    // Create entries table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `entries` (
          `id` int NOT NULL AUTO_INCREMENT,
          `entry_number` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
          `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
          `whatsapp` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
          `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
          `is_verified` tinyint(1) DEFAULT '0',
          `verified_at` datetime DEFAULT NULL,
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `entry_number` (`entry_number`),
          KEY `idx_whatsapp` (`whatsapp`),
          KEY `idx_phone` (`phone`),
          KEY `idx_entry_number` (`entry_number`),
          KEY `idx_verified` (`is_verified`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $tablesCreated++;
    
    // Create otps table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `otps` (
          `id` int NOT NULL AUTO_INCREMENT,
          `entry_id` int NOT NULL,
          `otp_code` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
          `otp_type` enum('whatsapp','phone','both') COLLATE utf8mb4_unicode_ci DEFAULT 'both',
          `is_used` tinyint(1) DEFAULT '0',
          `attempts` int DEFAULT '0',
          `expires_at` datetime NOT NULL,
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_otp_code` (`otp_code`),
          KEY `idx_entry_id` (`entry_id`),
          KEY `idx_expires_at` (`expires_at`),
          CONSTRAINT `otps_ibfk_1` FOREIGN KEY (`entry_id`) REFERENCES `entries` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $tablesCreated++;
    
    // Create otp_attempts table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `otp_attempts` (
          `id` int NOT NULL AUTO_INCREMENT,
          `entry_id` int DEFAULT NULL,
          `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
          `attempt_type` enum('generate','verify') COLLATE utf8mb4_unicode_ci NOT NULL,
          `is_successful` tinyint(1) DEFAULT '0',
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `entry_id` (`entry_id`),
          KEY `idx_ip_address` (`ip_address`),
          KEY `idx_created_at` (`created_at`),
          CONSTRAINT `otp_attempts_ibfk_1` FOREIGN KEY (`entry_id`) REFERENCES `entries` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $tablesCreated++;
    
    // Create notification_logs table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `notification_logs` (
          `id` int NOT NULL AUTO_INCREMENT,
          `entry_id` int NOT NULL,
          `notification_type` enum('whatsapp','sms') COLLATE utf8mb4_unicode_ci NOT NULL,
          `recipient` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
          `subject` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
          `message` text COLLATE utf8mb4_unicode_ci,
          `status` enum('pending','sent','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
          `response` text COLLATE utf8mb4_unicode_ci,
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_entry_id` (`entry_id`),
          KEY `idx_status` (`status`),
          CONSTRAINT `notification_logs_ibfk_1` FOREIGN KEY (`entry_id`) REFERENCES `entries` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $tablesCreated++;
    
    // Create settings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `settings` (
          `id` int NOT NULL AUTO_INCREMENT,
          `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
          `setting_value` text COLLATE utf8mb4_unicode_ci,
          `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
          `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `setting_key` (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $tablesCreated++;
    
    // Insert default settings
    $stmt = $pdo->prepare("
        INSERT INTO `settings` (`setting_key`, `setting_value`) 
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`
    ");
    
    $defaultSettings = [
        ['otp_length', '6'],
        ['otp_expiry_minutes', '10'],
        ['max_otp_attempts', '3'],
        ['email_service', 'smtp'],
        ['sms_service', 'log'],
        ['site_name', 'LOTD'],
        ['setup_completed', '0']
    ];
    
    foreach ($defaultSettings as $setting) {
        $stmt->execute($setting);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Database tables created successfully!',
        'tables_created' => $tablesCreated,
        'database' => $dbname
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database setup failed',
        'error' => $e->getMessage()
    ]);
}
