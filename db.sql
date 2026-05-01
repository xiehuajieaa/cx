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
  `sales_channel` VARCHAR(100) DEFAULT NULL,
  `manual_link` VARCHAR(255) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
   `remarks` TEXT DEFAULT NULL,
   `query_count` INT NOT NULL DEFAULT 0,
   `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `product_templates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `template_name` VARCHAR(100) NOT NULL,
  `product_type` VARCHAR(50) NOT NULL,
  `product_name` VARCHAR(100) NOT NULL,
  `product_model` VARCHAR(100) NOT NULL,
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

-- 默认管理员账号
-- 用户名: admin, 密码: admin123
-- 密码已用 SHA256(密码+盐值) 加密存储，首次登录后自动升级为 bcrypt
INSERT INTO `admins` (`username`, `password`) VALUES ('admin', 'sha256$+KkdopL/NA1WGPV+hoH22x2DmmbSdMs1WUdStutYtLY=');

-- Initial demo data (optional)
INSERT INTO `products` (`sn`, `product_type`, `product_name`, `product_model`, `manual_link`, `image`, `remarks`) VALUES
('MOB-20260410-DEMO', '手机', '旗舰手机 14 Pro', 'M2104K10AC', 'https://example.com/manual/phone.pdf', 'uploads/demo.jpg', '演示数据');

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
('log_retention_days', '30'),
('api_token', ''),
('factory_api_enabled', '0'),
('factory_api_token', '')
ON DUPLICATE KEY UPDATE `config_value` = VALUES(`config_value`);
