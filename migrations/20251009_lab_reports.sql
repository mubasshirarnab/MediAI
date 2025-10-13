-- Migration: Lab Reports schema alignment (2025-10-09)
-- Database: mediai

START TRANSACTION;

-- Create table if it doesn't exist with correct references
CREATE TABLE IF NOT EXISTS `lab_reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,                 -- references users.id (patient user id)
  `test_name` VARCHAR(255) NOT NULL,
  `report_file` VARCHAR(255) NOT NULL,
  `uploaded_by` INT NOT NULL,                -- references hospitals.user_id (hospital user id)
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `report_date` DATE NOT NULL,
  INDEX `idx_patient` (`patient_id`),
  INDEX `idx_uploaded_by` (`uploaded_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add/Correct FKs idempotently
-- Detect if a foreign key from patient_id -> patients(id) exists and drop it
SET @fk_to_drop := (
  SELECT CONSTRAINT_NAME
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lab_reports' AND COLUMN_NAME = 'patient_id'
    AND REFERENCED_TABLE_NAME = 'patients' AND REFERENCED_COLUMN_NAME = 'id'
  LIMIT 1
);
SET @sql := IF(@fk_to_drop IS NOT NULL, CONCAT('ALTER TABLE `lab_reports` DROP FOREIGN KEY ', @fk_to_drop), 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Detect if a foreign key from uploaded_by -> hospitals(id) exists and drop it
SET @fk_to_drop2 := (
  SELECT CONSTRAINT_NAME
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lab_reports' AND COLUMN_NAME = 'uploaded_by'
    AND REFERENCED_TABLE_NAME = 'hospitals' AND REFERENCED_COLUMN_NAME = 'id'
  LIMIT 1
);
SET @sql2 := IF(@fk_to_drop2 IS NOT NULL, CONCAT('ALTER TABLE `lab_reports` DROP FOREIGN KEY ', @fk_to_drop2), 'SELECT 1');
PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2;

-- Ensure columns are INT NOT NULL
ALTER TABLE `lab_reports`
  MODIFY COLUMN `patient_id` INT NOT NULL,
  MODIFY COLUMN `uploaded_by` INT NOT NULL;

-- Ensure correct FKs exist: patient_id -> users(id); uploaded_by -> hospitals(user_id)
-- Drop existing FKs if they point elsewhere
SET @fk_patient := (
  SELECT CONSTRAINT_NAME
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lab_reports' AND COLUMN_NAME = 'patient_id'
    AND REFERENCED_TABLE_NAME = 'users' AND REFERENCED_COLUMN_NAME = 'id'
  LIMIT 1
);
SET @sql3 := IF(@fk_patient IS NULL,
  'ALTER TABLE `lab_reports` ADD CONSTRAINT `fk_lr_patient_user` FOREIGN KEY (`patient_id`) REFERENCES `users`(`id`) ON DELETE CASCADE',
  'SELECT 1');
PREPARE stmt3 FROM @sql3; EXECUTE stmt3; DEALLOCATE PREPARE stmt3;

SET @fk_hospital := (
  SELECT CONSTRAINT_NAME
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'lab_reports' AND COLUMN_NAME = 'uploaded_by'
    AND REFERENCED_TABLE_NAME = 'hospitals' AND REFERENCED_COLUMN_NAME = 'user_id'
  LIMIT 1
);
SET @sql4 := IF(@fk_hospital IS NULL,
  'ALTER TABLE `lab_reports` ADD CONSTRAINT `fk_lr_hospital_user` FOREIGN KEY (`uploaded_by`) REFERENCES `hospitals`(`user_id`) ON DELETE CASCADE',
  'SELECT 1');
PREPARE stmt4 FROM @sql4; EXECUTE stmt4; DEALLOCATE PREPARE stmt4;

COMMIT;
