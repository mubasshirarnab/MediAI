-- =====================================================
-- HOSPITAL BILLING SYSTEM - COMPLETE SQL SETUP
-- MediAI Hospital Billing Management System
-- =====================================================

-- 1. PATIENT LEDGER TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS patient_ledger (
  id INT AUTO_INCREMENT PRIMARY KEY,
  patient_id INT NOT NULL,
  transaction_type ENUM('charge', 'payment', 'refund', 'discount') NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  description TEXT,
  reference_id INT DEFAULT NULL, -- Reference to bills, payments, etc.
  reference_type VARCHAR(50) DEFAULT NULL, -- 'bill', 'payment', 'refund'
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created_by INT DEFAULT NULL,
  FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_patient_ledger (patient_id, created_at),
  INDEX idx_transaction_type (transaction_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. BILLS TABLE (Enhanced)
-- =====================================================
-- Add new columns to existing bills table
ALTER TABLE bills ADD COLUMN IF NOT EXISTS bill_type ENUM('advance', 'interim', 'final') DEFAULT 'final';
ALTER TABLE bills ADD COLUMN IF NOT EXISTS due_date DATE DEFAULT NULL;
ALTER TABLE bills ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE bills ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE bills ADD COLUMN IF NOT EXISTS total_amount DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE bills ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE bills ADD COLUMN IF NOT EXISTS balance_amount DECIMAL(10,2) DEFAULT 0.00;
ALTER TABLE bills ADD COLUMN IF NOT EXISTS insurance_claim_id VARCHAR(100) DEFAULT NULL;
ALTER TABLE bills ADD COLUMN IF NOT EXISTS corporate_client_id INT DEFAULT NULL;
ALTER TABLE bills ADD COLUMN IF NOT EXISTS created_by INT DEFAULT NULL;
ALTER TABLE bills ADD COLUMN IF NOT EXISTS updated_by INT DEFAULT NULL;
ALTER TABLE bills ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Add foreign keys
ALTER TABLE bills ADD CONSTRAINT fk_bills_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE bills ADD CONSTRAINT fk_bills_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL;

-- 3. BILL ITEMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS bill_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bill_id INT NOT NULL,
  item_type ENUM('service', 'medicine', 'test', 'room', 'doctor_fee', 'nursing', 'surgery', 'other') NOT NULL,
  item_name VARCHAR(255) NOT NULL,
  item_description TEXT,
  quantity INT DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL,
  total_price DECIMAL(10,2) NOT NULL,
  discount_percentage DECIMAL(5,2) DEFAULT 0.00,
  discount_amount DECIMAL(10,2) DEFAULT 0.00,
  final_price DECIMAL(10,2) NOT NULL,
  service_date DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE,
  INDEX idx_bill_items (bill_id, item_type),
  INDEX idx_service_date (service_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. PAYMENTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bill_id INT NOT NULL,
  patient_id INT NOT NULL,
  payment_method ENUM('cash', 'card', 'mobile_banking', 'bank_transfer', 'cheque', 'insurance', 'corporate') NOT NULL,
  payment_amount DECIMAL(10,2) NOT NULL,
  payment_reference VARCHAR(255) DEFAULT NULL,
  payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'completed',
  transaction_id VARCHAR(255) DEFAULT NULL,
  bank_name VARCHAR(100) DEFAULT NULL,
  cheque_number VARCHAR(50) DEFAULT NULL,
  cheque_date DATE DEFAULT NULL,
  mobile_banking_provider VARCHAR(50) DEFAULT NULL,
  mobile_number VARCHAR(20) DEFAULT NULL,
  created_by INT DEFAULT NULL,
  notes TEXT,
  FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE,
  FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_payments_bill (bill_id),
  INDEX idx_payments_patient (patient_id),
  INDEX idx_payments_method (payment_method),
  INDEX idx_payments_date (payment_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. DISCOUNTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS discounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  discount_name VARCHAR(255) NOT NULL,
  discount_type ENUM('percentage', 'fixed_amount') NOT NULL,
  discount_value DECIMAL(10,2) NOT NULL,
  applicable_to ENUM('all', 'specific_patient', 'corporate', 'insurance') DEFAULT 'all',
  applicable_patient_id INT DEFAULT NULL,
  applicable_corporate_id INT DEFAULT NULL,
  applicable_insurance_id INT DEFAULT NULL,
  start_date DATE NOT NULL,
  end_date DATE DEFAULT NULL,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created_by INT DEFAULT NULL,
  FOREIGN KEY (applicable_patient_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_discounts_active (is_active, start_date, end_date),
  INDEX idx_discounts_applicable (applicable_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. PACKAGES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS packages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  package_name VARCHAR(255) NOT NULL,
  package_description TEXT,
  package_type ENUM('maternity', 'surgery', 'cardiology', 'orthopedic', 'general', 'custom') NOT NULL,
  total_price DECIMAL(10,2) NOT NULL,
  duration_days INT DEFAULT NULL,
  includes TEXT, -- JSON or text description of what's included
  exclusions TEXT, -- JSON or text description of what's excluded
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created_by INT DEFAULT NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_packages_active (is_active),
  INDEX idx_packages_type (package_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 7. PACKAGE ITEMS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS package_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  package_id INT NOT NULL,
  item_type ENUM('service', 'medicine', 'test', 'room', 'doctor_fee', 'nursing', 'surgery', 'other') NOT NULL,
  item_name VARCHAR(255) NOT NULL,
  item_description TEXT,
  quantity INT DEFAULT 1,
  unit_price DECIMAL(10,2) NOT NULL,
  total_price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
  INDEX idx_package_items (package_id, item_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 8. INSURANCE COMPANIES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS insurance_companies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_name VARCHAR(255) NOT NULL,
  contact_person VARCHAR(255) DEFAULT NULL,
  contact_email VARCHAR(255) DEFAULT NULL,
  contact_phone VARCHAR(20) DEFAULT NULL,
  address TEXT,
  policy_prefix VARCHAR(50) DEFAULT NULL,
  co_payment_percentage DECIMAL(5,2) DEFAULT 0.00,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created_by INT DEFAULT NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_insurance_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 9. CORPORATE CLIENTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS corporate_clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_name VARCHAR(255) NOT NULL,
  contact_person VARCHAR(255) DEFAULT NULL,
  contact_email VARCHAR(255) DEFAULT NULL,
  contact_phone VARCHAR(20) DEFAULT NULL,
  address TEXT,
  credit_limit DECIMAL(10,2) DEFAULT 0.00,
  payment_terms_days INT DEFAULT 30,
  discount_percentage DECIMAL(5,2) DEFAULT 0.00,
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  created_by INT DEFAULT NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_corporate_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 10. DOCTOR SHARES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS doctor_shares (
  id INT AUTO_INCREMENT PRIMARY KEY,
  doctor_id INT NOT NULL,
  bill_id INT NOT NULL,
  share_type ENUM('consultation', 'surgery', 'procedure', 'commission') NOT NULL,
  share_percentage DECIMAL(5,2) DEFAULT 0.00,
  share_amount DECIMAL(10,2) NOT NULL,
  payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
  payment_date DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE,
  INDEX idx_doctor_shares (doctor_id, payment_status),
  INDEX idx_bill_shares (bill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 11. REFUNDS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS refunds (
  id INT AUTO_INCREMENT PRIMARY KEY,
  bill_id INT NOT NULL,
  patient_id INT NOT NULL,
  refund_amount DECIMAL(10,2) NOT NULL,
  refund_reason TEXT NOT NULL,
  refund_method ENUM('cash', 'card', 'bank_transfer', 'mobile_banking') NOT NULL,
  refund_reference VARCHAR(255) DEFAULT NULL,
  refund_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  refund_status ENUM('pending', 'approved', 'processed', 'completed') DEFAULT 'pending',
  approved_by INT DEFAULT NULL,
  processed_by INT DEFAULT NULL,
  notes TEXT,
  FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE,
  FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_refunds_bill (bill_id),
  INDEX idx_refunds_patient (patient_id),
  INDEX idx_refunds_status (refund_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 12. SERVICE CHARGES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS service_charges (
  id INT AUTO_INCREMENT PRIMARY KEY,
  service_name VARCHAR(255) NOT NULL,
  service_type ENUM('consultation', 'surgery', 'procedure', 'test', 'medicine', 'room', 'nursing', 'other') NOT NULL,
  department VARCHAR(100) DEFAULT NULL,
  base_price DECIMAL(10,2) NOT NULL,
  unit VARCHAR(50) DEFAULT 'per_service',
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  created_by INT DEFAULT NULL,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_service_charges (service_type, is_active),
  INDEX idx_service_department (department)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 13. SERVICE TARIFFS TABLE (price groups per service)
-- =====================================================
CREATE TABLE IF NOT EXISTS service_tariffs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  service_id INT NOT NULL,
  price_group ENUM('general', 'vip', 'corporate') NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_service_group (service_id, price_group),
  FOREIGN KEY (service_id) REFERENCES service_charges(id) ON DELETE CASCADE,
  INDEX idx_tariff_group (price_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 13. INSERT SAMPLE DATA
-- =====================================================

-- Sample Service Charges
INSERT INTO service_charges (service_name, service_type, department, base_price, unit) VALUES
('General Consultation', 'consultation', 'General Medicine', 500.00, 'per_visit'),
('Cardiology Consultation', 'consultation', 'Cardiology', 800.00, 'per_visit'),
('Surgery Consultation', 'consultation', 'Surgery', 1000.00, 'per_visit'),
('Emergency Consultation', 'consultation', 'Emergency', 1200.00, 'per_visit'),
('CBC Test', 'test', 'Laboratory', 300.00, 'per_test'),
('X-Ray Chest', 'test', 'Radiology', 400.00, 'per_test'),
('CT Scan', 'test', 'Radiology', 2000.00, 'per_test'),
('MRI', 'test', 'Radiology', 5000.00, 'per_test'),
('General Ward Bed', 'room', 'General Ward', 2000.00, 'per_day'),
('Private Room', 'room', 'Private Ward', 5000.00, 'per_day'),
('ICU Bed', 'room', 'ICU', 8000.00, 'per_day'),
('Nursing Care', 'nursing', 'Nursing', 500.00, 'per_day'),
('Surgery - Appendectomy', 'surgery', 'Surgery', 15000.00, 'per_surgery'),
('Surgery - Gallbladder', 'surgery', 'Surgery', 25000.00, 'per_surgery');

-- Sample Service Tariffs (overrides per price group)
INSERT INTO service_tariffs (service_id, price_group, price)
SELECT id, 'general', base_price FROM service_charges
ON DUPLICATE KEY UPDATE price = VALUES(price);

-- Slightly higher for VIP, discounted for corporate (example ratios)
INSERT INTO service_tariffs (service_id, price_group, price)
SELECT id, 'vip', ROUND(base_price * 1.15, 2) FROM service_charges
ON DUPLICATE KEY UPDATE price = VALUES(price);

INSERT INTO service_tariffs (service_id, price_group, price)
SELECT id, 'corporate', ROUND(base_price * 0.9, 2) FROM service_charges
ON DUPLICATE KEY UPDATE price = VALUES(price);

-- Sample Insurance Companies
INSERT INTO insurance_companies (company_name, contact_person, contact_email, contact_phone, co_payment_percentage) VALUES
('Green Delta Insurance', 'Mr. Ahmed', 'ahmed@greendelta.com', '01700000001', 20.00),
('Pragati Insurance', 'Ms. Fatima', 'fatima@pragati.com', '01700000002', 15.00),
('Reliance Insurance', 'Mr. Karim', 'karim@reliance.com', '01700000003', 25.00),
('MetLife Insurance', 'Ms. Rina', 'rina@metlife.com', '01700000004', 10.00);

-- Sample Corporate Clients
INSERT INTO corporate_clients (company_name, contact_person, contact_email, contact_phone, credit_limit, payment_terms_days, discount_percentage) VALUES
('ABC Corporation', 'Mr. Rahman', 'rahman@abc.com', '01700000005', 100000.00, 30, 10.00),
('XYZ Limited', 'Ms. Begum', 'begum@xyz.com', '01700000006', 50000.00, 45, 5.00),
('DEF Industries', 'Mr. Hossain', 'hossain@def.com', '01700000007', 200000.00, 60, 15.00);

-- Sample Packages
INSERT INTO packages (package_name, package_description, package_type, total_price, duration_days, includes, exclusions) VALUES
('Maternity Package', 'Complete maternity care including delivery', 'maternity', 50000.00, 3, 'Delivery, Room, Nursing, Medicine', 'Complications, ICU'),
('Heart Surgery Package', 'Complete heart surgery package', 'cardiology', 200000.00, 7, 'Surgery, ICU, Medicine, Follow-up', 'Pre-existing conditions'),
('General Surgery Package', 'General surgery with room and care', 'surgery', 75000.00, 5, 'Surgery, Room, Nursing, Medicine', 'Post-surgery complications');

-- Sample Discounts
INSERT INTO discounts (discount_name, discount_type, discount_value, applicable_to, start_date, end_date) VALUES
('Senior Citizen Discount', 'percentage', 10.00, 'all', '2024-01-01', '2024-12-31'),
('Student Discount', 'percentage', 15.00, 'all', '2024-01-01', '2024-12-31'),
('Corporate Discount', 'percentage', 20.00, 'corporate', '2024-01-01', '2024-12-31'),
('Insurance Discount', 'percentage', 25.00, 'insurance', '2024-01-01', '2024-12-31');

-- =====================================================
-- BILLING SYSTEM SETUP COMPLETE
-- =====================================================
-- 
-- Features Available:
-- ✅ Patient Ledger - Complete financial profile
-- ✅ Automated Charge Capture - Auto-add charges
-- ✅ Multiple Bill Types - Advance, Interim, Final
-- ✅ Detailed Itemized Billing - Service-wise breakdown
-- ✅ Multiple Payment Modes - Cash, Card, Mobile, Bank
-- ✅ Discount Management - Percentage and fixed amount
-- ✅ Package Management - Pre-defined packages
-- ✅ Insurance Billing - Insurance company integration
-- ✅ Corporate Billing - Corporate client management
-- ✅ Due Management - Outstanding amount tracking
-- ✅ Refund Management - Refund processing
-- ✅ Doctor Share Calculation - Automatic commission
-- ✅ Service Charges - Configurable pricing
-- ✅ Service Tariffs - General/VIP/Corporate price groups
-- ✅ Comprehensive Reporting - All financial reports
-- 
-- =====================================================
