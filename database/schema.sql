-- =====================================================
-- ShelfAlert - Computerized Product Expiry Alert System
-- Database Schema for Ace Supermarket
-- =====================================================
-- Run this script in phpMyAdmin or MySQL CLI
-- Make sure database 'shelfalert' exists first

-- Drop existing tables if rebuilding (comment out in production)
-- SET FOREIGN_KEY_CHECKS = 0;
-- DROP TABLE IF EXISTS stock_movements, alerts, products, suppliers, categories;
-- DROP TABLE IF EXISTS role_permissions, permissions, user_role_assignments, users, user_roles;
-- DROP TABLE IF EXISTS system_settings, activity_logs;
-- SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- USER MANAGEMENT TABLES
-- =====================================================

-- User Roles Table
CREATE TABLE IF NOT EXISTS user_roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Permissions Table
CREATE TABLE IF NOT EXISTS permissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    permission_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    module VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    user_type ENUM('admin', 'staff') DEFAULT 'staff',
    is_active BOOLEAN DEFAULT TRUE,
    must_change_password BOOLEAN DEFAULT FALSE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User Role Assignments (Many-to-Many)
CREATE TABLE IF NOT EXISTS user_role_assignments (
    assignment_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES user_roles(role_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_role (user_id, role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Role Permissions (Many-to-Many)
CREATE TABLE IF NOT EXISTS role_permissions (
    role_permission_id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    FOREIGN KEY (role_id) REFERENCES user_roles(role_id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- PRODUCT MANAGEMENT TABLES
-- =====================================================

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_code VARCHAR(10) NOT NULL UNIQUE,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Suppliers Table
CREATE TABLE IF NOT EXISTS suppliers (
    supplier_id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_code VARCHAR(20) NOT NULL UNIQUE,
    supplier_name VARCHAR(150) NOT NULL,
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    city VARCHAR(50),
    state VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products Table (Core Table)
CREATE TABLE IF NOT EXISTS products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    product_code VARCHAR(30) NOT NULL UNIQUE,
    barcode VARCHAR(50),
    product_name VARCHAR(200) NOT NULL,
    category_id INT,
    supplier_id INT,
    description TEXT,
    unit_of_measure VARCHAR(20) DEFAULT 'piece',
    unit_price DECIMAL(12, 2) DEFAULT 0.00,
    cost_price DECIMAL(12, 2) DEFAULT 0.00,
    quantity INT DEFAULT 0,
    reorder_level INT DEFAULT 10,
    storage_location VARCHAR(100),
    manufacture_date DATE,
    expiry_date DATE NOT NULL,
    batch_number VARCHAR(50),
    status ENUM('active', 'expired', 'discontinued', 'out_of_stock') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(supplier_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_expiry_date (expiry_date),
    INDEX idx_category (category_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- ALERT MANAGEMENT TABLES
-- =====================================================

-- Alerts Table
CREATE TABLE IF NOT EXISTS alerts (
    alert_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    alert_type ENUM('expired', 'critical', 'warning', 'low_stock') NOT NULL,
    alert_message TEXT NOT NULL,
    days_until_expiry INT,
    status ENUM('active', 'acknowledged', 'resolved') DEFAULT 'active',
    acknowledged_by INT,
    acknowledged_at DATETIME,
    resolved_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (acknowledged_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_alert_type (alert_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- INVENTORY TRACKING TABLES
-- =====================================================

-- Stock Movements Table (Audit Trail)
CREATE TABLE IF NOT EXISTS stock_movements (
    movement_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    movement_type ENUM('in', 'out', 'adjustment', 'expired_removal', 'return') NOT NULL,
    quantity INT NOT NULL,
    quantity_before INT NOT NULL,
    quantity_after INT NOT NULL,
    reference_number VARCHAR(50),
    notes TEXT,
    performed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_product (product_id),
    INDEX idx_movement_type (movement_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- SYSTEM CONFIGURATION TABLES
-- =====================================================

-- System Settings Table
CREATE TABLE IF NOT EXISTS system_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity Logs Table
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Default Roles
INSERT INTO user_roles (role_name, description) VALUES 
('Admin', 'Full system access - can manage users, settings, and all modules'),
('Staff', 'Standard access - can manage products and view reports')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Default Permissions
INSERT INTO permissions (permission_name, description, module) VALUES 
('view_dashboard', 'View dashboard', 'dashboard'),
('manage_products', 'Create, edit, delete products', 'products'),
('view_products', 'View product list', 'products'),
('manage_categories', 'Manage product categories', 'categories'),
('manage_suppliers', 'Manage suppliers', 'suppliers'),
('view_alerts', 'View expiry alerts', 'alerts'),
('acknowledge_alerts', 'Acknowledge alerts', 'alerts'),
('view_reports', 'View all reports', 'reports'),
('export_reports', 'Export reports to CSV/PDF', 'reports'),
('manage_users', 'Create, edit, delete users', 'users'),
('manage_settings', 'Manage system settings', 'settings')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- Assign permissions to Admin role (all permissions)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT role_id FROM user_roles WHERE role_name = 'Admin'),
    permission_id
FROM permissions
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Assign permissions to Staff role (limited)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 
    (SELECT role_id FROM user_roles WHERE role_name = 'Staff'),
    permission_id
FROM permissions 
WHERE permission_name IN (
    'view_dashboard', 
    'manage_products', 
    'view_products', 
    'view_alerts', 
    'acknowledge_alerts', 
    'view_reports'
)
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Default System Settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES 
('alert_critical_days', '7', 'number', 'Days before expiry to trigger critical alert'),
('alert_warning_days', '30', 'number', 'Days before expiry to trigger warning alert'),
('low_stock_threshold', '10', 'number', 'Default quantity to trigger low stock alert'),
('company_name', 'Ace Supermarket', 'string', 'Company name for reports'),
('enable_email_alerts', 'false', 'boolean', 'Enable email notifications for alerts'),
('alert_email', '', 'string', 'Email address for alert notifications'),
('date_format', 'Y-m-d', 'string', 'Date format for display'),
('currency', 'NGN', 'string', 'Default currency'),
('currency_symbol', '₦', 'string', 'Currency symbol')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Default Admin User (password: admin123 - CHANGE IN PRODUCTION!)
INSERT INTO users (username, email, password_hash, full_name, user_type, is_active) VALUES 
('admin', 'admin@acesupermarket.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', TRUE)
ON DUPLICATE KEY UPDATE full_name = VALUES(full_name);

-- Assign Admin role to default admin user
INSERT INTO user_role_assignments (user_id, role_id)
SELECT 
    (SELECT user_id FROM users WHERE username = 'admin'),
    (SELECT role_id FROM user_roles WHERE role_name = 'Admin')
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Sample Categories
INSERT INTO categories (category_code, category_name, description) VALUES 
('DAI', 'Dairy Products', 'Milk, cheese, yogurt, and other dairy items'),
('BEV', 'Beverages', 'Drinks, juices, sodas, and water'),
('SNK', 'Snacks', 'Chips, biscuits, and packaged snacks'),
('FRZ', 'Frozen Foods', 'Frozen vegetables, meats, and ready meals'),
('CND', 'Canned Goods', 'Canned vegetables, fruits, and preserved foods'),
('BAK', 'Bakery', 'Bread, pastries, and baked goods'),
('MET', 'Meat & Poultry', 'Fresh and processed meats'),
('PRD', 'Produce', 'Fresh fruits and vegetables'),
('GRC', 'Groceries', 'General grocery items'),
('HPC', 'Health & Personal Care', 'Toiletries and health products')
ON DUPLICATE KEY UPDATE category_name = VALUES(category_name);

-- Sample Supplier
INSERT INTO suppliers (supplier_code, supplier_name, contact_person, phone, email, address, city, state) VALUES 
('SUP001', 'Fresh Foods Nigeria Ltd', 'John Okafor', '08012345678', 'sales@freshfoods.ng', '15 Industrial Road', 'Lagos', 'Lagos'),
('SUP002', 'Nigerian Beverages Co', 'Mary Adeyemi', '08098765432', 'info@ngbeverages.com', '42 Commerce Street', 'Ibadan', 'Oyo'),
('SUP003', 'Dairy Best Limited', 'Peter Nnamdi', '08055551234', 'orders@dairybest.ng', '8 Dairy Lane', 'Abuja', 'FCT')
ON DUPLICATE KEY UPDATE supplier_name = VALUES(supplier_name);

-- =====================================================
-- VIEWS FOR REPORTING
-- =====================================================

-- View: Products with expiry status
CREATE OR REPLACE VIEW v_products_expiry_status AS
SELECT 
    p.*,
    c.category_name,
    c.category_code,
    s.supplier_name,
    DATEDIFF(p.expiry_date, CURDATE()) as days_until_expiry,
    CASE 
        WHEN DATEDIFF(p.expiry_date, CURDATE()) <= 0 THEN 'expired'
        WHEN DATEDIFF(p.expiry_date, CURDATE()) <= 7 THEN 'critical'
        WHEN DATEDIFF(p.expiry_date, CURDATE()) <= 30 THEN 'warning'
        ELSE 'good'
    END as expiry_status
FROM products p
LEFT JOIN categories c ON p.category_id = c.category_id
LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id;

-- View: Active alerts summary
CREATE OR REPLACE VIEW v_active_alerts AS
SELECT 
    a.*,
    p.product_name,
    p.product_code,
    p.expiry_date,
    c.category_name
FROM alerts a
JOIN products p ON a.product_id = p.product_id
LEFT JOIN categories c ON p.category_id = c.category_id
WHERE a.status = 'active';

-- View: Dashboard statistics
CREATE OR REPLACE VIEW v_dashboard_stats AS
SELECT 
    (SELECT COUNT(*) FROM products WHERE status = 'active') as total_products,
    (SELECT COUNT(*) FROM products WHERE status = 'active' AND DATEDIFF(expiry_date, CURDATE()) <= 7 AND DATEDIFF(expiry_date, CURDATE()) > 0) as expiring_soon,
    (SELECT COUNT(*) FROM products WHERE status = 'active' AND DATEDIFF(expiry_date, CURDATE()) <= 0) as expired_products,
    (SELECT COUNT(*) FROM products WHERE status = 'active' AND quantity <= reorder_level) as low_stock,
    (SELECT COUNT(*) FROM alerts WHERE status = 'active') as active_alerts,
    (SELECT COUNT(*) FROM categories WHERE is_active = 1) as total_categories,
    (SELECT COUNT(*) FROM suppliers WHERE is_active = 1) as total_suppliers;

-- =====================================================
-- END OF SCHEMA
-- =====================================================
