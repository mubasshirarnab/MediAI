-- Migration: Cabin Booking feature (2025-10-04)
-- Database: mediai

START TRANSACTION;

-- 1) Create cabins table if not exists
CREATE TABLE IF NOT EXISTS `cabins` (
  `cabin_id` INT AUTO_INCREMENT PRIMARY KEY,
  `cabin_number` VARCHAR(50) NOT NULL UNIQUE,
  `type` ENUM('general','deluxe','ICU') NOT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `availability` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2) If an old cabin_bookings table exists with legacy schema, rename it to preserve data
-- We detect legacy by missing column `booking_id`
SET @has_booking_id := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cabin_bookings' AND COLUMN_NAME = 'booking_id'
);

SET @tbl_exists := (
  SELECT COUNT(*) FROM information_schema.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cabin_bookings'
);

-- If table exists and does not have booking_id, rename it
SET @sql := IF(@tbl_exists = 1 AND @has_booking_id = 0,
  'RENAME TABLE `cabin_bookings` TO `cabin_bookings_legacy`',
  'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Create new cabin_bookings with required schema if it doesn't exist
CREATE TABLE IF NOT EXISTS `cabin_bookings` (
  `booking_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `cabin_id` INT NOT NULL,
  `booking_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `check_in` DATE NOT NULL,
  `check_out` DATE NOT NULL,
  `status` ENUM('booked','completed','cancelled') NOT NULL DEFAULT 'booked',
  CONSTRAINT `fk_cb_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cb_cabin` FOREIGN KEY (`cabin_id`) REFERENCES `cabins`(`cabin_id`) ON DELETE CASCADE,
  INDEX `idx_cabin_dates` (`cabin_id`, `check_in`, `check_out`),
  INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

COMMIT;
