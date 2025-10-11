-- =====================================================
-- ADMIN PAGE - SIMPLIFIED SQL SETUP
-- MediAI Admin Panel - Essential Tables Only
-- =====================================================

-- 1. ADD ADMIN ROLE TO ROLES TABLE
-- =====================================================
-- Update roles table enum to include admin
ALTER TABLE roles MODIFY COLUMN role_name ENUM('patient','doctor','hospital','admin') NOT NULL;

-- Insert admin role if not exists
INSERT IGNORE INTO roles (id, role_name) VALUES (4, 'admin');

-- 1.1. ADD IS_BLOCKED COLUMN TO USERS TABLE
-- =====================================================
-- Add is_blocked column to users table for admin blocking functionality
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_blocked TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=unblocked, 1=blocked by admin';

-- 2. CREATE ADMINS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  admin_role VARCHAR(100) NOT NULL,
  department VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. CREATE DEFAULT ADMIN USER
-- =====================================================
-- Password: admin123 (hashed)
INSERT IGNORE INTO users (id, name, email, password, phone, role_id, status, otp) 
VALUES (999, 'System Admin', 'admin@mediai.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '01700000000', 4, 'authorized', 0);

-- Insert admin details
INSERT IGNORE INTO admins (user_id, admin_role, department) 
VALUES (999, 'Super Admin', 'System Administration');

-- 4. VERIFY ADMIN USER CREATION
-- =====================================================
-- Check if admin user was created successfully
SELECT 
  u.id, 
  u.name, 
  u.email, 
  u.role_id, 
  r.role_name, 
  a.admin_role, 
  a.department 
FROM users u 
JOIN roles r ON u.role_id = r.id 
LEFT JOIN admins a ON u.id = a.user_id 
WHERE u.email = 'admin@mediai.com';

-- =====================================================
-- ADMIN PAGE SETUP COMPLETE
-- =====================================================
-- 
-- Admin Login Credentials:
-- Email: admin@mediai.com
-- Password: admin123
-- 
-- Essential Features Available:
-- ✅ User Management (View, Edit, Block, Delete)
-- ✅ Doctor Management (Add, Edit, Delete, Block/Unblock)
-- ✅ Hospital Management (Add, Edit, Block)
-- ✅ Appointment Management (View, Filter, Status Change)
-- ✅ Cabin Booking Management (View, Status Change)
-- ✅ Community Management (Moderate Posts, Delete)
-- ✅ Dashboard (Statistics, Recent Activity)
-- 
-- User Blocking System:
-- ✅ is_blocked column added to users table
-- ✅ Block = 1, Unblock = 0
-- ✅ Blocked users cannot login (login.php check)
-- ✅ Admin can block/unblock users from admin panel
-- ✅ Blocking prevents login but preserves user data
-- 
-- Admin Setup:
-- ✅ Admin role added to roles table
-- ✅ Admins table created for admin user management
-- ✅ Default admin user created (admin@mediai.com / admin123)
-- ✅ Admin verification query included
-- 
-- =====================================================