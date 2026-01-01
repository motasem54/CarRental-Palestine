-- ==================================================================
-- جدول العقود (Rental Contracts)
-- Rental Contracts Table
-- ==================================================================

CREATE TABLE IF NOT EXISTS `rental_contracts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rental_id` int(11) NOT NULL,
  `contract_type` enum('simple', 'with_promissory') NOT NULL DEFAULT 'simple',
  `has_promissory_note` tinyint(1) DEFAULT 0,
  `contract_path` varchar(255) DEFAULT NULL,
  `contract_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rental_id` (`rental_id`),
  KEY `contract_type` (`contract_type`),
  CONSTRAINT `rental_contracts_ibfk_1` FOREIGN KEY (`rental_id`) REFERENCES `rentals` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- END OF RENTAL CONTRACTS TABLE
-- ==================================================================