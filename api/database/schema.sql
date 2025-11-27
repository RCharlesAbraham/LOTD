-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 27, 2025 at 10:05 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE
= "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone
= "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lotd_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users`
(
  `id` int NOT NULL,
  `username` varchar
(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar
(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar
(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint
(1) DEFAULT '1',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON
UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`
id`,
`username
`, `email`, `password`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@gmail.com', '$2y$10$5GdenayY9poHnVRmgJCCTuCzBWBU5AGekEuH4NzsVeCjnJsDGJFUe', 1, '2025-11-27 15:06:56', '2025-11-27 15:04:47', '2025-11-27 15:06:56');

-- --------------------------------------------------------

--
-- Table structure for table `entries`
--

CREATE TABLE `entries`
(
  `id` int NOT NULL,
  `entry_number` varchar
(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar
(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar
(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar
(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_verified` tinyint
(1) DEFAULT '0',
  `verified_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON
UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `entries`
--

INSERT INTO `entries` (`
id`,
`entry_number
`, `name`, `email`, `phone`, `is_verified`, `verified_at`, `created_at`, `updated_at`) VALUES
(1, 'LOTDB94121', 'Demo', 'demo@gmail.com', '1234567890', 0, NULL, '2025-11-27 14:18:55', '2025-11-27 14:54:10'),
(2, 'LOTD265E4E', 'Charles Abraham R', 'demo@email.om', '7904617924', 0, NULL, '2025-11-27 14:34:14', '2025-11-27 14:34:14');

-- --------------------------------------------------------

--
-- Table structure for table `notification_logs`
--

CREATE TABLE `notification_logs`
(
  `id` int NOT NULL,
  `entry_id` int NOT NULL,
  `notification_type` enum
('email','sms') COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient` varchar
(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar
(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `status` enum
('pending','sent','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `response` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notification_logs`
--

INSERT INTO `notification_logs` (`
id`,
`entry_id
`, `notification_type`, `recipient`, `subject`, `message`, `status`, `response`, `created_at`) VALUES
(1, 1, 'email', 'demo@gmail.com', 'Your LOTD Verification Code', NULL, 'sent', '{\"success\":true,\"message\":\"Email sent successfully\"}', '2025-11-27 14:18:57'),
(2, 1, 'sms', '1234567890', NULL, 'Your LOTD verification code is: 697614. Valid for 10 minutes. Do not share this code.', 'sent', '{\"success\":true,\"message\":\"SMS logged successfully (test mode)\"}', '2025-11-27 14:18:57'),
(3, 2, 'email', 'demo@email.om', 'Your LOTD Verification Code', NULL, 'sent', '{\"success\":true,\"message\":\"Email sent successfully\"}', '2025-11-27 14:34:14'),
(4, 2, 'sms', '7904617924', NULL, 'Your LOTD verification code is: 465586. Valid for 10 minutes. Do not share this code.', 'sent', '{\"success\":true,\"message\":\"SMS logged successfully (test mode)\"}', '2025-11-27 14:34:14'),
(5, 1, 'email', 'demo@gmail.com', 'Your LOTD Verification Code', NULL, 'sent', '{\"success\":true,\"message\":\"Email sent successfully\"}', '2025-11-27 14:54:10'),
(6, 1, 'sms', '1234567890', NULL, 'Your LOTD verification code is: 565857. Valid for 10 minutes. Do not share this code.', 'sent', '{\"success\":true,\"message\":\"SMS logged successfully (test mode)\"}', '2025-11-27 14:54:10');

-- --------------------------------------------------------

--
-- Table structure for table `otps`
--

CREATE TABLE `otps`
(
  `id` int NOT NULL,
  `entry_id` int NOT NULL,
  `otp_code` varchar
(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `otp_type` enum
('email','phone','both') COLLATE utf8mb4_unicode_ci DEFAULT 'both',
  `is_used` tinyint
(1) DEFAULT '0',
  `attempts` int DEFAULT '0',
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otps`
--

INSERT INTO `otps` (`
id`,
`entry_id
`, `otp_code`, `otp_type`, `is_used`, `attempts`, `expires_at`, `created_at`) VALUES
(1, 1, '697614', 'both', 1, 0, '2025-11-27 08:58:55', '2025-11-27 14:18:55'),
(2, 2, '465586', 'both', 0, 0, '2025-11-27 09:14:14', '2025-11-27 14:34:14'),
(3, 1, '565857', 'both', 0, 0, '2025-11-27 09:34:10', '2025-11-27 14:54:10');

-- --------------------------------------------------------

--
-- Table structure for table `otp_attempts`
--

CREATE TABLE `otp_attempts`
(
  `id` int NOT NULL,
  `entry_id` int DEFAULT NULL,
  `ip_address` varchar
(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempt_type` enum
('generate','verify') COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_successful` tinyint
(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `otp_attempts`
--

INSERT INTO `otp_attempts` (`
id`,
`entry_id
`, `ip_address`, `attempt_type`, `is_successful`, `created_at`) VALUES
(1, 1, '::1', 'generate', 1, '2025-11-27 14:18:57'),
(2, 1, '::1', 'verify', 0, '2025-11-27 14:20:07'),
(3, 2, '127.0.0.1', 'generate', 1, '2025-11-27 14:34:14'),
(4, 1, '127.0.0.1', 'generate', 1, '2025-11-27 14:54:10');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings`
(
  `id` int NOT NULL,
  `setting_key` varchar
(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON
UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`
id`,
`setting_key
`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'otp_length', '6', '2025-11-27 15:04:47', '2025-11-27 15:04:47'),
(2, 'otp_expiry_minutes', '10', '2025-11-27 15:04:47', '2025-11-27 15:04:47'),
(3, 'max_otp_attempts', '3', '2025-11-27 15:04:47', '2025-11-27 15:04:47'),
(4, 'email_service', 'smtp', '2025-11-27 15:04:47', '2025-11-27 15:04:47'),
(5, 'sms_service', 'log', '2025-11-27 15:04:47', '2025-11-27 15:04:47'),
(6, 'site_name', 'LOTD', '2025-11-27 15:04:47', '2025-11-27 15:04:47'),
(7, 'setup_completed', '1', '2025-11-27 15:04:47', '2025-11-27 15:04:47'),
(8, 'setup_date', '2025-11-27 09:34:47', '2025-11-27 15:04:47', '2025-11-27 15:04:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
ADD PRIMARY KEY
(`id`),
ADD UNIQUE KEY `username`
(`username`),
ADD UNIQUE KEY `email`
(`email`);

--
-- Indexes for table `entries`
--
ALTER TABLE `entries`
ADD PRIMARY KEY
(`id`),
ADD UNIQUE KEY `entry_number`
(`entry_number`),
ADD KEY `idx_email`
(`email`),
ADD KEY `idx_phone`
(`phone`),
ADD KEY `idx_entry_number`
(`entry_number`),
ADD KEY `idx_verified`
(`is_verified`);

--
-- Indexes for table `notification_logs`
--
ALTER TABLE `notification_logs`
ADD PRIMARY KEY
(`id`),
ADD KEY `idx_entry_id`
(`entry_id`),
ADD KEY `idx_status`
(`status`);

--
-- Indexes for table `otps`
--
ALTER TABLE `otps`
ADD PRIMARY KEY
(`id`),
ADD KEY `idx_otp_code`
(`otp_code`),
ADD KEY `idx_entry_id`
(`entry_id`),
ADD KEY `idx_expires_at`
(`expires_at`);

--
-- Indexes for table `otp_attempts`
--
ALTER TABLE `otp_attempts`
ADD PRIMARY KEY
(`id`),
ADD KEY `entry_id`
(`entry_id`),
ADD KEY `idx_ip_address`
(`ip_address`),
ADD KEY `idx_created_at`
(`created_at`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
ADD PRIMARY KEY
(`id`),
ADD UNIQUE KEY `setting_key`
(`setting_key`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `entries`
--
ALTER TABLE `entries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notification_logs`
--
ALTER TABLE `notification_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `otps`
--
ALTER TABLE `otps`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `otp_attempts`
--
ALTER TABLE `otp_attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notification_logs`
--
ALTER TABLE `notification_logs`
ADD CONSTRAINT `notification_logs_ibfk_1` FOREIGN KEY
(`entry_id`) REFERENCES `entries`
(`id`) ON
DELETE CASCADE;

--
-- Constraints for table `otps`
--
ALTER TABLE `otps`
ADD CONSTRAINT `otps_ibfk_1` FOREIGN KEY
(`entry_id`) REFERENCES `entries`
(`id`) ON
DELETE CASCADE;

--
-- Constraints for table `otp_attempts`
--
ALTER TABLE `otp_attempts`
ADD CONSTRAINT `otp_attempts_ibfk_1` FOREIGN KEY
(`entry_id`) REFERENCES `entries`
(`id`) ON
DELETE
SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
