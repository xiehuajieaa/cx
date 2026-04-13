CREATE DATABASE IF NOT EXISTS `product_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `product_db`;

CREATE TABLE IF NOT EXISTS `product_types` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `type_name` VARCHAR(50) NOT NULL UNIQUE,
  `sn_prefix` VARCHAR(10) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO `product_types` (`type_name`, `sn_prefix`) VALUES
('手机', 'MOB'),
('电脑', 'LAP'),
('平板', 'TAB'),
('耳机', 'EAR'),
('手表', 'WAT'),
('其他', 'OTH');

CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sn` VARCHAR(50) NOT NULL UNIQUE,
  `sn_code` VARCHAR(100) DEFAULT NULL,
  `product_type` VARCHAR(50) NOT NULL,
  `product_name` VARCHAR(100) NOT NULL,
  `product_model` VARCHAR(100) NOT NULL,
  `manufacturing_date` DATE NOT NULL,
  `warranty_months` INT NOT NULL DEFAULT 12,
  `expiry_date` DATE NOT NULL,
  `sales_channel` VARCHAR(100) DEFAULT NULL,
  `manual_link` VARCHAR(255) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `product_templates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `template_name` VARCHAR(100) NOT NULL,
  `product_type` VARCHAR(50) NOT NULL,
  `product_name` VARCHAR(100) NOT NULL,
  `product_model` VARCHAR(100) NOT NULL,
  `warranty_months` INT NOT NULL DEFAULT 12,
  `sales_channel` VARCHAR(100) DEFAULT NULL,
  `manual_link` VARCHAR(255) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin account (password is 'admin')
-- Note: In production, passwords should be hashed using password_hash() in PHP.
-- For now, as per requirement, we will store it directly or hash it.
-- Let's use password_hash('admin', PASSWORD_DEFAULT) output for security.
-- Hash of 'admin': $2y$10$S45ZkYQpY/D1917C.fV0/uCj0qVl7Xj7gZ.6P/GvDqf5G9vV8kZ9u (may vary)
-- For simplicity and direct use, let's store it as 'admin' if requested, 
-- but normally it should be hashed. The user said "账号和密码都是admin".
INSERT INTO `admins` (`username`, `password`) VALUES ('admin', 'admin');

-- Initial demo data (optional)
INSERT INTO `products` (`sn`, `product_type`, `product_name`, `product_model`, `manufacturing_date`, `warranty_months`, `expiry_date`, `manual_link`, `image`, `remarks`) VALUES
('MOB-20260410-DEMO', '手机', '旗舰手机 14 Pro', 'M2104K10AC', '2026-04-10', 12, '2027-04-10', 'https://example.com/manual/phone.pdf', 'uploads/demo.jpg', '演示数据');

-- Logs table
CREATE TABLE IF NOT EXISTS `logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT NOT NULL,
  `username` VARCHAR(50) NOT NULL,
  `action` VARCHAR(50) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Config table for logs
CREATE TABLE IF NOT EXISTS `system_config` (
  `config_key` VARCHAR(50) PRIMARY KEY,
  `config_value` VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

INSERT INTO `system_config` (`config_key`, `config_value`) VALUES 
('log_enabled', '1'),
('log_retention_days', '30');
