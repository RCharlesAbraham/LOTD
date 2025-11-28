-- LOTD Database Schema - InfinityFree Compatible

SET SQL_MODE
= "NO_AUTO_VALUE_ON_ZERO";
SET time_zone
= "+00:00";

-- Table: admin_users
DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users`
(`id` int
(11) NOT NULL AUTO_INCREMENT, `username` varchar
(50) NOT NULL, `email` varchar
(255) NOT NULL, `password` varchar
(255) NOT NULL, `is_active` tinyint
(1) DEFAULT '1', `last_login` datetime DEFAULT NULL, `created_at` datetime DEFAULT NULL, `updated_at` datetime DEFAULT NULL, PRIMARY KEY
(`id`), UNIQUE KEY `username`
(`username`), UNIQUE KEY `email`
(`email`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Table: entries
DROP TABLE IF EXISTS `entries`;
CREATE TABLE `entries`
(`id` int
(11) NOT NULL AUTO_INCREMENT, `entry_number` varchar
(20) NOT NULL, `name` varchar
(255) NOT NULL, `whatsapp` varchar
(20) NOT NULL, `phone` varchar
(20) NOT NULL, `is_verified` tinyint
(1) DEFAULT '0', `verified_at` datetime DEFAULT NULL, `created_at` datetime DEFAULT NULL, `updated_at` datetime DEFAULT NULL, PRIMARY KEY
(`id`), UNIQUE KEY `entry_number`
(`entry_number`), KEY `idx_whatsapp`
(`whatsapp`), KEY `idx_phone`
(`phone`), KEY `idx_verified`
(`is_verified`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Table: otps
DROP TABLE IF EXISTS `otps`;
CREATE TABLE `otps`
(`id` int
(11) NOT NULL AUTO_INCREMENT, `entry_id` int
(11) NOT NULL, `otp_code` varchar
(6) NOT NULL, `otp_type` varchar
(10) DEFAULT 'both', `is_used` tinyint
(1) DEFAULT '0', `attempts` int
(11) DEFAULT '0', `expires_at` datetime NOT NULL, `created_at` datetime DEFAULT NULL, PRIMARY KEY
(`id`), KEY `idx_otp_code`
(`otp_code`), KEY `idx_entry_id`
(`entry_id`), KEY `idx_expires_at`
(`expires_at`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Table: otp_attempts
DROP TABLE IF EXISTS `otp_attempts`;
CREATE TABLE `otp_attempts`
(`id` int
(11) NOT NULL AUTO_INCREMENT, `entry_id` int
(11) DEFAULT NULL, `ip_address` varchar
(45) NOT NULL, `attempt_type` varchar
(10) NOT NULL, `is_successful` tinyint
(1) DEFAULT '0', `created_at` datetime DEFAULT NULL, PRIMARY KEY
(`id`), KEY `idx_entry_id`
(`entry_id`), KEY `idx_ip_address`
(`ip_address`), KEY `idx_created_at`
(`created_at`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Table: notification_logs
DROP TABLE IF EXISTS `notification_logs`;
CREATE TABLE `notification_logs`
(`id` int
(11) NOT NULL AUTO_INCREMENT, `entry_id` int
(11) NOT NULL, `notification_type` varchar
(10) NOT NULL, `recipient` varchar
(255) NOT NULL, `subject` varchar
(255) DEFAULT NULL, `message` text, `status` varchar
(10) DEFAULT 'pending', `response` text, `created_at` datetime DEFAULT NULL, PRIMARY KEY
(`id`), KEY `idx_entry_id`
(`entry_id`), KEY `idx_status`
(`status`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Table: settings
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings`
(`id` int
(11) NOT NULL AUTO_INCREMENT, `setting_key` varchar
(100) NOT NULL, `setting_value` text, `created_at` datetime DEFAULT NULL, `updated_at` datetime DEFAULT NULL, PRIMARY KEY
(`id`), UNIQUE KEY `setting_key`
(`setting_key`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- Default settings
INSERT INTO `settings` (`
setting_key`,
`setting_value
`, `created_at`, `updated_at`) VALUES
('otp_length', '6', '2025-11-27 00:00:00', '2025-11-27 00:00:00');
INSERT INTO `settings` (`
setting_key`,
`setting_value
`, `created_at`, `updated_at`) VALUES
('otp_expiry_minutes', '10', '2025-11-27 00:00:00', '2025-11-27 00:00:00');
INSERT INTO `settings` (`
setting_key`,
`setting_value
`, `created_at`, `updated_at`) VALUES
('max_otp_attempts', '3', '2025-11-27 00:00:00', '2025-11-27 00:00:00');
INSERT INTO `settings` (`
setting_key`,
`setting_value
`, `created_at`, `updated_at`) VALUES
('email_service', 'smtp', '2025-11-27 00:00:00', '2025-11-27 00:00:00');
INSERT INTO `settings` (`
setting_key`,
`setting_value
`, `created_at`, `updated_at`) VALUES
('sms_service', 'log', '2025-11-27 00:00:00', '2025-11-27 00:00:00');
INSERT INTO `settings` (`
setting_key`,
`setting_value
`, `created_at`, `updated_at`) VALUES
('site_name', 'LOTD', '2025-11-27 00:00:00', '2025-11-27 00:00:00');
INSERT INTO `settings` (`
setting_key`,
`setting_value
`, `created_at`, `updated_at`) VALUES
('setup_completed', '1', '2025-11-27 00:00:00', '2025-11-27 00:00:00');
INSERT INTO `settings` (`
setting_key`,
`setting_value
`, `created_at`, `updated_at`) VALUES
('setup_date', '2025-11-27 00:00:00', '2025-11-27 00:00:00', '2025-11-27 00:00:00');
