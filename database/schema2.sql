

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET FOREIGN_KEY_CHECKS = 0;



-- --------------------------------------------------------

--
-- Table structure for table `users` (BASE TABLE)
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `user_type` enum('admin','staff') DEFAULT 'staff',
  `is_active` tinyint(1) DEFAULT 1,
  `must_change_password` tinyint(1) DEFAULT 0,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `full_name`, `phone`, `user_type`, `is_active`, `must_change_password`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@acesupermarket.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', NULL, 'admin', 1, 0, '2026-02-09 16:16:11', '2026-01-27 13:41:30', '2026-02-09 15:16:11'),
(2, 'Quadri', 'adekunle@acesupermarket.com', '$2y$10$F/1DfNBpy7EYoD.c6Hpir.ypR67xem25Pqn6VRszcXo9YyDPpezJm', 'Adekunle', NULL, 'staff', 1, 0, '2026-01-28 06:54:21', '2026-01-27 14:45:26', '2026-01-28 05:54:21'),
(3, 'Solomon', 'solomon@acesupermarket.com', '$2y$10$CNkkWneOExRf/4Rvg4/4CewTJq2Q5lbfkJzMKLk07Zb/AEN12x27W', 'Solomon Adeleye', NULL, 'staff', 0, 0, '2026-01-28 10:55:15', '2026-01-27 16:36:23', '2026-02-09 16:29:54');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles` (BASE TABLE)
--

CREATE TABLE `user_roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `user_roles` (`role_id`, `role_name`, `description`, `is_active`, `created_at`, `updated_at`, `permissions`) VALUES
(1, 'Admin', 'Full system access - can manage users, settings, and all modules', 1, '2026-01-27 13:41:30', '2026-01-27 15:42:27', '[\"dashboard.view\",\"products.view\",\"products.create\",\"products.edit\",\"products.delete\",\"categories.view\",\"categories.manage\",\"suppliers.view\",\"suppliers.manage\",\"alerts.view\",\"alerts.acknowledge\",\"reports.view\",\"reports.export\",\"users.view\",\"users.manage\",\"settings.view\",\"settings.manage\",\"roles.view\",\"roles.manage\"]'),
(2, 'Staff', 'Standard access - can manage products and view reports', 1, '2026-01-27 13:41:30', '2026-01-27 15:25:03', '[\"dashboard.view\",\"products.view\",\"products.create\",\"products.edit\",\"alerts.view\",\"alerts.acknowledge\",\"reports.view\"]'),
(3, 'Manager', 'Full system access - can manage users, settings, and all modules', 1, '2026-01-27 16:22:08', '2026-01-27 16:22:08', '[\"dashboard.view\",\"products.view\",\"products.create\",\"products.edit\",\"products.delete\",\"categories.view\",\"categories.manage\",\"suppliers.view\",\"suppliers.manage\",\"alerts.view\",\"alerts.acknowledge\",\"reports.view\",\"reports.export\",\"users.view\",\"users.manage\",\"settings.view\",\"settings.manage\"]');

-- --------------------------------------------------------

--
-- Table structure for table `permissions` (BASE TABLE)
--

CREATE TABLE `permissions` (
  `permission_id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `module` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`permission_id`),
  UNIQUE KEY `permission_name` (`permission_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `permissions` (`permission_id`, `permission_name`, `description`, `module`, `created_at`) VALUES
(1, 'view_dashboard', 'View dashboard', 'dashboard', '2026-01-27 13:41:30'),
(2, 'manage_products', 'Create, edit, delete products', 'products', '2026-01-27 13:41:30'),
(3, 'view_products', 'View product list', 'products', '2026-01-27 13:41:30'),
(4, 'manage_categories', 'Manage product categories', 'categories', '2026-01-27 13:41:30'),
(5, 'manage_suppliers', 'Manage suppliers', 'suppliers', '2026-01-27 13:41:30'),
(6, 'view_alerts', 'View expiry alerts', 'alerts', '2026-01-27 13:41:30'),
(7, 'acknowledge_alerts', 'Acknowledge alerts', 'alerts', '2026-01-27 13:41:30'),
(8, 'view_reports', 'View all reports', 'reports', '2026-01-27 13:41:30'),
(9, 'export_reports', 'Export reports to CSV/PDF', 'reports', '2026-01-27 13:41:30'),
(10, 'manage_users', 'Create, edit, delete users', 'users', '2026-01-27 13:41:30'),
(11, 'manage_settings', 'Manage system settings', 'settings', '2026-01-27 13:41:30');

-- --------------------------------------------------------

--
-- Table structure for table `categories` (BASE TABLE)
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_code` varchar(10) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_code` (`category_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `categories` (`category_id`, `category_code`, `category_name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'DAI', 'Dairy Products', 'Milk, cheese, yogurt, and other dairy items', 1, '2026-01-27 13:41:30', '2026-01-27 13:41:30'),
(2, 'BEV', 'Beverages', 'Drinks, juices, sodas, and water', 1, '2026-01-27 13:41:30', '2026-01-27 13:41:30'),
(3, 'SNK', 'Snacks', 'Chips, biscuits, and packaged snacks', 1, '2026-01-27 13:41:30', '2026-01-27 13:41:30'),
(4, 'FRZ', 'Frozen Foods', 'Frozen vegetables, meats, and ready meals', 1, '2026-01-27 13:41:30', '2026-01-27 13:41:30'),
(5, 'CND', 'Canned Goods', 'Canned vegetables, fruits, and preserved foods', 1, '2026-01-27 13:41:30', '2026-01-27 13:41:30'),
(6, 'BAK', 'Bakery', 'Bread, pastries, and baked goods', 1, '2026-01-27 13:41:30', '2026-01-27 13:41:30'),
(7, 'MET', 'Meat & Poultry', 'Fresh and processed meats', 1, '2026-01-27 13:41:30', '2026-01-27 13:41:30'),
(8, 'PRD', 'Produce', 'Fresh fruits and vegetables', 1, '2026-01-27 13:41:30', '2026-01-27 13:41:30'),
(9, 'GRC', 'Groceries', 'General grocery items', 1, '2026-01-27 13:41:30', '2026-01-27 13:41:30'),
(10, 'HPC', 'Health & Personal Care', 'Toiletries and health products', 1, '2026-01-27 13:41:30', '2026-01-27 13:41:30');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers` (BASE TABLE)
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(20) NOT NULL,
  `supplier_name` varchar(150) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`supplier_id`),
  UNIQUE KEY `supplier_code` (`supplier_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `suppliers` (`supplier_id`, `supplier_code`, `supplier_name`, `contact_person`, `phone`, `email`, `address`, `city`, `state`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'SUP001', 'Fresh Foods Nigeria Ltd', 'John Okafor', '08012345678', 'sales@freshfoods.ng', '15 Industrial Road', 'Lagos', 'Lagos', 1, '2026-01-27 13:41:30', '2026-01-27 13:41:30'),
(2, 'SUP002', 'Nigerian Beverages Co', 'Mary Adeyemi', '08098765432', 'info@ngbeverages.com', '42 Commerce Street', 'Ibadan', 'Oyo', 1, '2026-01-27 13:41:30', '2026-01-27 13:41:30'),
(3, 'SUP003', 'Dairy Best Limited', 'Peter Nnamdi', '08055551234', 'orders@dairybest.ng', '8 Dairy Lane', 'Abuja', 'FCT', 1, '2026-01-27 13:41:30', '2026-01-27 13:41:30'),
(4, 'SUPBE320', 'Belersdorf', 'Jelili Ogunbunmi', '07042277326', 'jogunbumi@bayebusinesssolutions.com', '', NULL, NULL, 1, '2026-01-28 14:36:34', '2026-01-28 14:36:34');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings` (BASE TABLE)
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `system_settings` (`setting_id`, `setting_key`, `setting_value`, `setting_type`, `description`, `updated_at`) VALUES
(1, 'alert_critical_days', '7', 'number', 'Days before expiry to trigger critical alert', '2026-01-27 13:41:30'),
(2, 'alert_warning_days', '30', 'number', 'Days before expiry to trigger warning alert', '2026-01-27 13:41:30'),
(3, 'low_stock_threshold', '10', 'number', 'Default quantity to trigger low stock alert', '2026-01-27 13:41:30'),
(4, 'company_name', 'Ace Supermarket', 'string', 'Company name for reports', '2026-01-27 13:41:30'),
(5, 'enable_email_alerts', 'false', 'boolean', 'Enable email notifications for alerts', '2026-01-27 13:41:30'),
(6, 'alert_email', '', 'string', 'Email address for alert notifications', '2026-01-27 13:41:30'),
(7, 'date_format', 'Y-m-d', 'string', 'Date format for display', '2026-01-27 13:41:30'),
(8, 'currency', 'NGN', 'string', 'Default currency', '2026-01-27 13:41:30'),
(9, 'currency_symbol', 'Ôéª', 'string', 'Currency symbol', '2026-01-27 13:41:30'),
(11, 'company_address', '', 'string', NULL, '2026-01-27 15:04:50'),
(12, 'company_phone', '', 'string', NULL, '2026-01-27 15:04:50'),
(13, 'company_email', '', 'string', NULL, '2026-01-27 15:04:50'),
(14, 'warning_days', '30', 'string', NULL, '2026-01-27 15:04:50'),
(15, 'critical_days', '10', 'string', NULL, '2026-01-27 15:04:50');

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs` (DEPENDENT on users)
--

CREATE TABLE `activity_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_role_assignments` (DEPENDENT on users, user_roles)
--

CREATE TABLE `user_role_assignments` (
  `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`assignment_id`),
  UNIQUE KEY `unique_user_role` (`user_id`,`role_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `user_role_assignments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `user_role_assignments_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `user_role_assignments` (`assignment_id`, `user_id`, `role_id`, `assigned_at`) VALUES
(1, 1, 1, '2026-01-27 13:41:30'),
(3, 2, 3, '2026-01-27 16:34:39'),
(5, 3, 1, '2026-01-28 09:53:01');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions` (DEPENDENT on user_roles, permissions)
--

CREATE TABLE `role_permissions` (
  `role_permission_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`role_permission_id`),
  UNIQUE KEY `unique_role_permission` (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`) ON DELETE CASCADE,
  CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `role_permissions` (`role_permission_id`, `role_id`, `permission_id`) VALUES
(9, 1, 1),
(4, 1, 2),
(10, 1, 3),
(3, 1, 4),
(6, 1, 5),
(8, 1, 6),
(1, 1, 7),
(11, 1, 8),
(2, 1, 9),
(7, 1, 10),
(5, 1, 11),
(19, 2, 1),
(17, 2, 2),
(20, 2, 3),
(18, 2, 6),
(16, 2, 7),
(21, 2, 8);

-- --------------------------------------------------------

--
-- Table structure for table `products` (DEPENDENT on categories, suppliers, users)
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(30) NOT NULL,
  `barcode` varchar(50) DEFAULT NULL,
  `product_name` varchar(200) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `unit_of_measure` varchar(20) DEFAULT 'piece',
  `unit_price` decimal(12,2) DEFAULT 0.00,
  `cost_price` decimal(12,2) DEFAULT 0.00,
  `quantity` int(11) DEFAULT 0,
  `reorder_level` int(11) DEFAULT 10,
  `storage_location` varchar(100) DEFAULT NULL,
  `manufacture_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `status` enum('active','expired','discontinued','out_of_stock') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`product_id`),
  UNIQUE KEY `product_code` (`product_code`),
  KEY `supplier_id` (`supplier_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_expiry_date` (`expiry_date`),
  KEY `idx_category` (`category_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL,
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`) ON DELETE SET NULL,
  CONSTRAINT `products_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `products` (`product_id`, `product_code`, `barcode`, `product_name`, `category_id`, `supplier_id`, `description`, `unit_of_measure`, `unit_price`, `cost_price`, `quantity`, `reorder_level`, `storage_location`, `manufacture_date`, `expiry_date`, `batch_number`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 'HPC-00001', '40005900925022', 'Nivea Radiant & Beauty', 10, 4, '', 'pack', 17000.00, 16000.00, 390, 10, '', '2025-02-08', '2027-01-29', 'BN-002', 'active', 1, '2026-01-28 16:13:01', '2026-01-29 06:11:30'),
(3, 'BEV-00001', '4773737272828282', 'Milo 3 in 1', 2, 3, '', 'pack', 3300.00, 3000.00, 200, 10, 'Aisle 1, Shelf D', '2024-01-03', '2027-02-03', 'BN-003', 'active', 1, '2026-02-03 15:26:50', '2026-02-09 15:46:07');

-- --------------------------------------------------------

--
-- Table structure for table `product_batches` (DEPENDENT on products)
--

CREATE TABLE `product_batches` (
  `batch_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `manufacture_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `initial_quantity` int(11) NOT NULL,
  `current_quantity` int(11) NOT NULL,
  `status` enum('active','depleted','expired_removal') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`batch_id`),
  KEY `product_id` (`product_id`),
  KEY `idx_expiry` (`expiry_date`),
  CONSTRAINT `product_batches_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `product_batches` (`batch_id`, `product_id`, `batch_number`, `manufacture_date`, `expiry_date`, `initial_quantity`, `current_quantity`, `status`, `created_at`) VALUES
(1, 2, 'BN-001', '2025-02-08', '2026-02-08', 10, 0, 'depleted', '2026-01-28 16:13:01'),
(2, 2, 'BN-002', NULL, '2027-01-29', 400, 390, 'active', '2026-01-29 06:00:00'),
(3, 3, 'BN-001', '2024-01-03', '2026-02-08', 500, 0, 'depleted', '2026-02-03 15:26:50'),
(4, 3, 'BN-003', NULL, '2027-02-03', 200, 200, 'active', '2026-02-03 15:28:39');

-- --------------------------------------------------------

--
-- Table structure for table `alerts` (DEPENDENT on products, product_batches, users)
--

CREATE TABLE `alerts` (
  `alert_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `alert_type` enum('expired','critical','warning','low_stock') NOT NULL,
  `alert_message` text NOT NULL,
  `days_until_expiry` int(11) DEFAULT NULL,
  `status` enum('active','acknowledged','resolved') DEFAULT 'active',
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`alert_id`),
  KEY `product_id` (`product_id`),
  KEY `acknowledged_by` (`acknowledged_by`),
  KEY `idx_status` (`status`),
  KEY `idx_alert_type` (`alert_type`),
  KEY `idx_created_at` (`created_at`),
  KEY `fk_alert_batch` (`batch_id`),
  CONSTRAINT `alerts_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  CONSTRAINT `alerts_ibfk_2` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_alert_batch` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`batch_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `alerts` (`alert_id`, `product_id`, `batch_id`, `alert_type`, `alert_message`, `days_until_expiry`, `status`, `acknowledged_by`, `acknowledged_at`, `resolved_at`, `created_at`) VALUES
(1, 2, 1, 'warning', 'Batch BN-001 of \'Nivea Radiant & Beauty\' (HPC-00001) expires in 10 days on Feb 08, 2026', 10, 'resolved', NULL, NULL, '2026-01-29 07:02:34', '2026-01-29 05:57:50'),
(2, 3, 3, 'expired', 'Batch BN-001 of \'Milo 3 in 1\' (BEV-00001) has EXPIRED on Feb 08, 2026', -1, 'resolved', NULL, NULL, '2026-02-09 16:46:07', '2026-02-09 15:16:11');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements` (DEPENDENT on products, users)
--

CREATE TABLE `stock_movements` (
  `movement_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `movement_type` enum('in','out','adjustment','expired_removal','return') NOT NULL,
  `quantity` int(11) NOT NULL,
  `quantity_before` int(11) NOT NULL,
  `quantity_after` int(11) NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`movement_id`),
  KEY `performed_by` (`performed_by`),
  KEY `idx_product` (`product_id`),
  KEY `idx_movement_type` (`movement_type`),
  CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  CONSTRAINT `stock_movements_ibfk_2` FOREIGN KEY (`performed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `stock_movements` (`movement_id`, `product_id`, `movement_type`, `quantity`, `quantity_before`, `quantity_after`, `reference_number`, `notes`, `performed_by`, `created_at`) VALUES
(1, 2, 'in', 400, 10, 410, NULL, '', 1, '2026-01-29 06:00:00'),
(2, 2, 'expired_removal', 5, 410, 405, NULL, '', 1, '2026-01-29 06:02:16'),
(3, 2, 'out', 5, 405, 400, NULL, '', 1, '2026-01-29 06:02:34'),
(4, 2, 'out', 10, 400, 390, NULL, '', 1, '2026-01-29 06:11:30'),
(5, 3, 'in', 200, 500, 700, NULL, '', 1, '2026-02-03 15:28:39'),
(6, 3, 'expired_removal', 500, 700, 200, NULL, 'Resolving expiry alert for Batch: BN-001', 1, '2026-02-09 15:46:07');

-- --------------------------------------------------------

--
-- Table structure for table `cron_logs` (NO DEPENDENCIES)
--

CREATE TABLE `cron_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `job_name` varchar(100) NOT NULL,
  `status` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `executed_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_job_name` (`job_name`),
  KEY `idx_executed_at` (`executed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `cron_logs` (`log_id`, `job_name`, `status`, `details`, `executed_at`) VALUES
(1, 'check_expiry', 'completed', '{\"alerts_created\":0,\"triggered_by\":\"manual\"}', '2026-01-27 15:37:43'),
(2, 'check_expiry', 'completed', '{\"alerts_created\":0,\"triggered_by\":\"manual\"}', '2026-01-28 09:35:23'),
(3, 'check_expiry', 'completed', '{\"alerts_created\":0,\"triggered_by\":\"manual\"}', '2026-01-30 13:11:59');

-- --------------------------------------------------------

--
-- Structure for view `v_active_alerts`
--

CREATE OR REPLACE VIEW `v_active_alerts` AS SELECT `a`.`alert_id` AS `alert_id`, `a`.`product_id` AS `product_id`, `a`.`alert_type` AS `alert_type`, `a`.`alert_message` AS `alert_message`, `a`.`days_until_expiry` AS `days_until_expiry`, `a`.`status` AS `status`, `a`.`acknowledged_by` AS `acknowledged_by`, `a`.`acknowledged_at` AS `acknowledged_at`, `a`.`resolved_at` AS `resolved_at`, `a`.`created_at` AS `created_at`, `p`.`product_name` AS `product_name`, `p`.`product_code` AS `product_code`, `p`.`expiry_date` AS `expiry_date`, `c`.`category_name` AS `category_name` FROM ((`alerts` `a` join `products` `p` on(`a`.`product_id` = `p`.`product_id`)) left join `categories` `c` on(`p`.`category_id` = `c`.`category_id`)) WHERE `a`.`status` = 'active' ;

-- --------------------------------------------------------

--
-- Structure for view `v_dashboard_stats`
--

CREATE OR REPLACE VIEW `v_dashboard_stats` AS SELECT (select count(0) from `products` where `products`.`status` = 'active') AS `total_products`, (select count(0) from `products` where `products`.`status` = 'active' and to_days(`products`.`expiry_date`) - to_days(curdate()) <= 7 and to_days(`products`.`expiry_date`) - to_days(curdate()) > 0) AS `expiring_soon`, (select count(0) from `products` where `products`.`status` = 'active' and to_days(`products`.`expiry_date`) - to_days(curdate()) <= 0) AS `expired_products`, (select count(0) from `products` where `products`.`status` = 'active' and `products`.`quantity` <= `products`.`reorder_level`) AS `low_stock`, (select count(0) from `alerts` where `alerts`.`status` = 'active') AS `active_alerts`, (select count(0) from `categories` where `categories`.`is_active` = 1) AS `total_categories`, (select count(0) from `suppliers` where `suppliers`.`is_active` = 1) AS `total_suppliers` ;

-- --------------------------------------------------------

--
-- Structure for view `v_products_expiry_status`
--

CREATE OR REPLACE VIEW `v_products_expiry_status` AS SELECT `p`.`product_id` AS `product_id`, `p`.`product_code` AS `product_code`, `p`.`barcode` AS `barcode`, `p`.`product_name` AS `product_name`, `p`.`category_id` AS `category_id`, `p`.`supplier_id` AS `supplier_id`, `p`.`description` AS `description`, `p`.`unit_of_measure` AS `unit_of_measure`, `p`.`unit_price` AS `unit_price`, `p`.`cost_price` AS `cost_price`, `p`.`quantity` AS `quantity`, `p`.`reorder_level` AS `reorder_level`, `p`.`storage_location` AS `storage_location`, `p`.`manufacture_date` AS `manufacture_date`, `p`.`expiry_date` AS `expiry_date`, `p`.`batch_number` AS `batch_number`, `p`.`status` AS `status`, `p`.`created_by` AS `created_by`, `p`.`created_at` AS `created_at`, `p`.`updated_at` AS `updated_at`, `c`.`category_name` AS `category_name`, `c`.`category_code` AS `category_code`, `s`.`supplier_name` AS `supplier_name`, to_days(`p`.`expiry_date`) - to_days(curdate()) AS `days_until_expiry`, CASE WHEN to_days(`p`.`expiry_date`) - to_days(curdate()) <= 0 THEN 'expired' WHEN to_days(`p`.`expiry_date`) - to_days(curdate()) <= 7 THEN 'critical' WHEN to_days(`p`.`expiry_date`) - to_days(curdate()) <= 30 THEN 'warning' ELSE 'good' END AS `expiry_status` FROM ((`products` `p` left join `categories` `c` on(`p`.`category_id` = `c`.`category_id`)) left join `suppliers` `s` on(`p`.`supplier_id` = `s`.`supplier_id`)) ;

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;


