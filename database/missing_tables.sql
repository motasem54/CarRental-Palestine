-- ==================================================================
-- Missing Tables for Palestine Car Rental System
-- الجداول الناقصة لنظام تأجير السيارات
-- Date: 2025-12-22
-- ==================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ==================================================================
-- 1. جدول آراء العملاء (Testimonials)
-- ==================================================================

CREATE TABLE IF NOT EXISTS `testimonials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(100) NOT NULL,
  `customer_title` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `rating` int(1) NOT NULL DEFAULT 5,
  `image` varchar(255) DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `is_featured` (`is_featured`),
  KEY `rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- بيانات تجريبية لآراء العملاء
INSERT INTO `testimonials` (`customer_name`, `customer_title`, `message`, `rating`, `is_featured`, `status`, `display_order`) VALUES
('أحمد محمود', 'رجل أعمال', 'خدمة ممتازة وسيارات نظيفة. أنصح بالتعامل معهم بشدة!', 5, 1, 'approved', 1),
('سارة الحسن', 'موظفة', 'تجربة رائعة، موظفين محترمين وأسعار معقولة جداً', 5, 1, 'approved', 2),
('محمد عيسى', 'مهندس', 'أفضل شركة تأجير سيارات في فلسطين، سيارات حديثة ونظيفة', 5, 1, 'approved', 3),
('ليلى عبد الله', 'معلمة', 'سرعة في التعامل وحرفية عالية. شكراً لكم!', 5, 0, 'approved', 4);

-- ==================================================================
-- 2. جدول سجل الأمان (Security Logs)
-- ==================================================================

CREATE TABLE IF NOT EXISTS `security_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `event_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `event_type` (`event_type`),
  KEY `severity` (`severity`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `security_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- الفهارس الإضافية
-- ==================================================================

CREATE INDEX idx_security_event ON security_logs(event_type, severity);
CREATE INDEX idx_security_date ON security_logs(created_at);

-- ==================================================================
-- END OF MISSING TABLES
-- ==================================================================