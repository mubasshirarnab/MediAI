-- Simplified Hospital Settings Database Schema
-- Only essential tables for real hospital settings

-- Main hospital settings table (stores all settings in JSON format)
CREATE TABLE IF NOT EXISTS hospital_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    hospital_id INT NOT NULL,
    setting_category VARCHAR(50) NOT NULL,
    setting_data JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_hospital_category (hospital_id, setting_category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default settings for hospital
INSERT INTO hospital_settings (hospital_id, setting_category, setting_data) VALUES
(7, 'general', '{"hospital_name": "United Hospital", "hospital_address": "Plot 15, Road 71, Gulshan Dhaka 1212, Bangladesh", "hospital_phone": "+880-123-456-789", "hospital_email": "info@unitedhospital.com", "hospital_website": "https://unitedhospital.com", "operating_hours": {"monday": "24/7", "tuesday": "24/7", "wednesday": "24/7", "thursday": "24/7", "friday": "24/7", "saturday": "24/7", "sunday": "24/7"}, "timezone": "Asia/Dhaka", "currency": "BDT", "language": "en", "theme": "light", "date_format": "Y-m-d", "time_format": "H:i:s"}'),
(7, 'billing', '{"tax_rate": 0.00, "service_charge": 0.00, "currency": "BDT", "auto_billing_enabled": false}'),
(7, 'notifications', '{"email_enabled": true, "sms_enabled": true, "push_enabled": true}'),
(7, 'system', '{"maintenance_mode": false, "backup_frequency": "daily", "audit_log_enabled": true}');
