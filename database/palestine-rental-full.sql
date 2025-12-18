-- ==================================================================
-- 🇵🇸 CarRental Palestine - Complete Database Schema
-- نظام تأجير السيارات - فلسطين
-- Version: 1.0.0
-- Date: 2024-12-18
-- Timezone: Asia/Jerusalem
-- Currency: ILS (₪)
-- ==================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- ==================================================================
-- 1. جدول المستخدمين (Users)
-- ==================================================================

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','employee','customer') NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `role` (`role`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- مستخدم افتراضي
INSERT INTO `users` (`username`, `password`, `email`, `full_name`, `phone`, `role`, `status`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@carrental.ps', 'مدير النظام', '+970599123456', 'admin', 'active');
-- Password: Admin@123

-- ==================================================================
-- 2. جدول العملاء (Customers)
-- ==================================================================

CREATE TABLE IF NOT EXISTS `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(100) NOT NULL,
  `id_number` varchar(20) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT 'رام الله',
  `driver_license` varchar(50) DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT 5.0,
  `total_bookings` int(11) DEFAULT 0,
  `loyalty_points` int(11) DEFAULT 0,
  `loyalty_level` enum('bronze','silver','gold','platinum') DEFAULT 'bronze',
  `status` enum('active','inactive','blacklist') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_number` (`id_number`),
  KEY `phone` (`phone`),
  KEY `city` (`city`),
  KEY `loyalty_level` (`loyalty_level`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- 3. جدول السيارات (Cars)
-- ==================================================================

CREATE TABLE IF NOT EXISTS `cars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plate_number` varchar(20) NOT NULL,
  `brand` varchar(50) NOT NULL,
  `model` varchar(50) NOT NULL,
  `year` int(4) NOT NULL,
  `color` varchar(30) NOT NULL,
  `type` enum('sedan','suv','van','luxury','sport','economy') NOT NULL DEFAULT 'sedan',
  `transmission` enum('manual','automatic') NOT NULL DEFAULT 'manual',
  `fuel_type` enum('petrol','diesel','hybrid','electric') NOT NULL DEFAULT 'petrol',
  `seats` int(2) NOT NULL DEFAULT 5,
  `daily_rate` decimal(10,2) NOT NULL,
  `weekly_rate` decimal(10,2) DEFAULT NULL,
  `monthly_rate` decimal(10,2) DEFAULT NULL,
  `mileage` int(11) DEFAULT 0,
  `status` enum('available','rented','maintenance','reserved') NOT NULL DEFAULT 'available',
  `condition` enum('excellent','good','fair','poor') DEFAULT 'good',
  `features` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `insurance_company` varchar(100) DEFAULT NULL,
  `insurance_policy` varchar(100) DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `last_maintenance` date DEFAULT NULL,
  `next_maintenance` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plate_number` (`plate_number`),
  KEY `status` (`status`),
  KEY `type` (`type`),
  KEY `brand` (`brand`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- 4. جدول صور السيارات (Car Images)
-- ==================================================================

CREATE TABLE IF NOT EXISTS `car_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `car_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `car_id` (`car_id`),
  CONSTRAINT `car_images_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- 5. جدول الحجوزات/الإيجارات (Rentals)
-- ==================================================================

CREATE TABLE IF NOT EXISTS `rentals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rental_number` varchar(20) NOT NULL,
  `car_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `actual_return_date` date DEFAULT NULL,
  `pickup_location` varchar(100) DEFAULT 'رام الله',
  `return_location` varchar(100) DEFAULT 'رام الله',
  `total_days` int(11) NOT NULL,
  `daily_rate` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `discount_reason` varchar(100) DEFAULT NULL,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `penalty_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `remaining_amount` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('pending','partial','paid','refunded') DEFAULT 'pending',
  `status` enum('pending','confirmed','active','completed','cancelled') NOT NULL DEFAULT 'pending',
  `mileage_start` int(11) DEFAULT NULL,
  `mileage_end` int(11) DEFAULT NULL,
  `fuel_level_start` enum('empty','quarter','half','three_quarter','full') DEFAULT 'full',
  `fuel_level_end` enum('empty','quarter','half','three_quarter','full') DEFAULT NULL,
  `contract_signed` tinyint(1) DEFAULT 0,
  `contract_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rental_number` (`rental_number`),
  KEY `car_id` (`car_id`),
  KEY `customer_id` (`customer_id`),
  KEY `status` (`status`),
  KEY `payment_status` (`payment_status`),
  KEY `start_date` (`start_date`),
  KEY `end_date` (`end_date`),
  CONSTRAINT `rentals_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`),
  CONSTRAINT `rentals_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `rentals_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- 6. جدول المدفوعات (Payments)
-- ==================================================================

CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_number` varchar(20) NOT NULL,
  `rental_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','credit_card','bank_transfer','check') NOT NULL DEFAULT 'cash',
  `payment_date` date NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_number` (`payment_number`),
  KEY `rental_id` (`rental_id`),
  KEY `payment_date` (`payment_date`),
  KEY `payment_method` (`payment_method`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`id`),
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- 7. جدول الصيانة (Maintenance)
-- ==================================================================

CREATE TABLE IF NOT EXISTS `maintenance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `car_id` int(11) NOT NULL,
  `maintenance_type` enum('regular','repair','inspection','other') NOT NULL DEFAULT 'regular',
  `description` text NOT NULL,
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `maintenance_date` date NOT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `mileage` int(11) DEFAULT NULL,
  `service_provider` varchar(100) DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `car_id` (`car_id`),
  KEY `maintenance_date` (`maintenance_date`),
  KEY `status` (`status`),
  CONSTRAINT `maintenance_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`),
  CONSTRAINT `maintenance_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- 8. جدول برامج الولاء (Loyalty Programs)
-- ==================================================================

CREATE TABLE IF NOT EXISTS `loyalty_programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level` enum('bronze','silver','gold','platinum') NOT NULL,
  `min_points` int(11) NOT NULL,
  `points_per_shekel` decimal(5,2) DEFAULT 1.00,
  `discount_percentage` decimal(5,2) DEFAULT 0.00,
  `benefits` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `loyalty_programs` (`level`, `min_points`, `points_per_shekel`, `discount_percentage`, `benefits`) VALUES
('bronze', 0, 1.00, 0.00, 'برنامج العضوية الأساسي'),
('silver', 500, 1.50, 5.00, 'خصم 5% + نقاط مضاعفة'),
('gold', 1500, 2.00, 10.00, 'خصم 10% + أولوية في الحجز'),
('platinum', 5000, 3.00, 15.00, 'خصم 15% + سيارة مجانية ليوم');

-- ==================================================================
-- 9. جدول نقاط العملاء (Customer Points)
-- ==================================================================

CREATE TABLE IF NOT EXISTS `customer_points` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `rental_id` int(11) DEFAULT NULL,
  `points` int(11) NOT NULL,
  `type` enum('earned','redeemed','expired','adjusted') NOT NULL DEFAULT 'earned',
  `description` varchar(255) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `rental_id` (`rental_id`),
  KEY `type` (`type`),
  CONSTRAINT `customer_points_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `customer_points_ibfk_2` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- 10. جدول الخصومات (Discounts)
-- ==================================================================

CREATE TABLE IF NOT EXISTS `discounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `type` enum('percentage','fixed','duration') NOT NULL DEFAULT 'percentage',
  `value` decimal(10,2) NOT NULL,
  `min_days` int(11) DEFAULT NULL,
  `max_uses` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- 11. جدول الغرامات (Penalties)
-- ==================================================================

CREATE TABLE IF NOT EXISTS `penalties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rental_id` int(11) NOT NULL,
  `penalty_type` enum('late_return','damage','fuel','traffic','other') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('pending','paid','waived') DEFAULT 'pending',
  `waived_by` int(11) DEFAULT NULL,
  `waived_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `rental_id` (`rental_id`),
  KEY `penalty_type` (`penalty_type`),
  KEY `status` (`status`),
  CONSTRAINT `penalties_ibfk_1` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`id`),
  CONSTRAINT `penalties_ibfk_2` FOREIGN KEY (`waived_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- 12. جدول إعدادات الغرامات (Penalty Settings)
-- ==================================================================

CREATE TABLE IF NOT EXISTS `penalty_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `penalty_type` varchar(50) NOT NULL,
  `amount_per_day` decimal(10,2) DEFAULT NULL,
  `fixed_amount` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `penalty_type` (`penalty_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `penalty_settings` (`penalty_type`, `amount_per_day`, `fixed_amount`, `description`, `is_active`) VALUES
('late_return', 50.00, NULL, 'غرامة التأخير عن موعد التسليم', 1),
('fuel', NULL, 100.00, 'غرامة عدم ملء الوقود', 1),
('damage', NULL, NULL, 'غرامة الأضرار (حسب التقييم)', 1);

-- ==================================================================
-- 13. جدول الحجوزات من الموقع (Online Bookings)
-- ==================================================================

CREATE TABLE IF NOT EXISTS `online_bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_number` varchar(20) NOT NULL,
  `car_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `id_number` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','cancelled','converted') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `rental_id` int(11) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_number` (`booking_number`),
  KEY `car_id` (`car_id`),
  KEY `status` (`status`),
  KEY `approved_by` (`approved_by`),
  KEY `rental_id` (`rental_id`),
  CONSTRAINT `online_bookings_ibfk_1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`),
  CONSTRAINT `online_bookings_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `online_bookings_ibfk_3` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- 14. جدول إعدادات الموقع (Website Settings)
-- ==================================================================

CREATE TABLE IF NOT EXISTS `website_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','textarea','image','color','boolean') DEFAULT 'text',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `website_settings` (`setting_key`, `setting_value`, `setting_type`) VALUES
('site_name', 'شركة تأجير السيارات - فلسطين', 'text'),
('site_logo', 'uploads/logos/logo.png', 'image'),
('site_email', 'info@carrental.ps', 'text'),
('site_phone', '+970599123456', 'text'),
('site_address', 'رام الله - فلسطين', 'text'),
('primary_color', '#FF5722', 'color'),
('secondary_color', '#121212', 'color'),
('currency', 'ILS', 'text'),
('currency_symbol', '₪', 'text'),
('timezone', 'Asia/Jerusalem', 'text'),
('whatsapp_enabled', '0', 'boolean'),
('sms_enabled', '0', 'boolean');

-- ==================================================================
-- 15. جدول سجل النشاطات (Activity Log)
-- ==================================================================

CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- 16. جدول الإعدادات العامة (System Settings)
-- ==================================================================

CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('company_name', 'شركة تأجير السيارات', 'general'),
('company_address', 'رام الله، فلسطين', 'general'),
('company_phone', '+970599123456', 'general'),
('company_email', 'info@carrental.ps', 'general'),
('default_city', 'رام الله', 'general'),
('currency', 'ILS', 'general'),
('currency_symbol', '₪', 'general'),
('tax_rate', '16.00', 'financial'),
('late_fee_per_day', '50.00', 'financial'),
('min_rental_days', '1', 'rental'),
('max_rental_days', '90', 'rental');

-- ==================================================================
-- الفهارس الإضافية (Additional Indexes)
-- ==================================================================

CREATE INDEX idx_rentals_dates ON rentals(start_date, end_date);
CREATE INDEX idx_cars_status ON cars(status, type);
CREATE INDEX idx_customers_loyalty ON customers(loyalty_level, loyalty_points);
CREATE INDEX idx_payments_date ON payments(payment_date, payment_method);

-- ==================================================================
-- END OF DATABASE SCHEMA
-- ==================================================================