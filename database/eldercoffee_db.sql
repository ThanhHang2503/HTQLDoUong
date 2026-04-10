-- ============================================================
-- ELDERCOFFEE DATABASE - Full Schema + Seed Data
-- Database: eldercoffee_db
-- Charset: utf8mb4_unicode_ci
-- Version: 2.0 (HTTT Complete)
-- ============================================================
CREATE DATABASE IF NOT EXISTS eldercoffee_db;
USE eldercoffee_db;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- DROP TABLES (nếu tồn tại) - đúng thứ tự phụ thuộc
-- ============================================================
DROP TABLE IF EXISTS `resignation_requests`;
DROP TABLE IF EXISTS `leave_requests`;
DROP TABLE IF EXISTS `salary_records`;
DROP TABLE IF EXISTS `employee_positions_history`;
DROP TABLE IF EXISTS `inventory_export_items`;
DROP TABLE IF EXISTS `inventory_exports`;
DROP TABLE IF EXISTS `stock_movements`;
DROP TABLE IF EXISTS `inventory_receipt_items`;
DROP TABLE IF EXISTS `inventory_receipts`;
DROP TABLE IF EXISTS `invoice_details`;
DROP TABLE IF EXISTS `invoices`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `items`;
DROP TABLE IF EXISTS `category`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `positions`;
DROP TABLE IF EXISTS `accounts`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `system_config`;
DROP TABLE IF EXISTS `activity_logs`;

-- ============================================================
-- 1. ROLES
-- ============================================================
CREATE TABLE `roles` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `display_name` VARCHAR(100) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_role_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `name`, `display_name`) VALUES
(1, 'admin',     'Quản trị viên'),
(2, 'manager',   'Quản lý'),
(3, 'sales',     'Nhân viên bán hàng'),
(4, 'warehouse', 'Nhân viên kho');

-- ============================================================
-- 2. POSITIONS (Chức vụ)
-- ============================================================
CREATE TABLE `positions` (
  `position_id`   INT NOT NULL AUTO_INCREMENT,
  `position_name` VARCHAR(100) NOT NULL,
  `base_salary`   BIGINT NOT NULL DEFAULT 0,
  `description`   TEXT DEFAULT NULL,
  `is_active`     TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `positions` (`position_id`, `position_name`, `base_salary`, `description`) VALUES
(1, 'Giám đốc',              20000000, 'Lãnh đạo cao nhất công ty'),
(2, 'Quản lý cấp cao',       15000000, 'Quản lý các bộ phận chính'),
(3, 'Nhân viên bán hàng',     8000000, 'Phụ trách bán hàng, tạo hóa đơn'),
(4, 'Nhân viên kho',          7500000, 'Quản lý nhập xuất kho'),
(5, 'Kế toán',               10000000, 'Quản lý tài chính, lương thưởng'),
(6, 'Nhân viên thử việc',     5000000, 'Nhân viên đang trong giai đoạn thử việc');

-- ============================================================
-- 3. ACCOUNTS
-- ============================================================
CREATE TABLE `accounts` (
  `account_id`  INT NOT NULL AUTO_INCREMENT,
  `full_name`   VARCHAR(150) NOT NULL,
  `email`       VARCHAR(150) NOT NULL,
  `password`    VARCHAR(255) NOT NULL,
  `role_id`     INT NOT NULL DEFAULT 3,
  `position_id` INT DEFAULT NULL,
  `phone`       VARCHAR(20) DEFAULT NULL,
  `address`     TEXT DEFAULT NULL,
  `birth_date`  DATE DEFAULT NULL,
  `gender`      ENUM('nam','nữ','khác') DEFAULT NULL,
  `avatar`      VARCHAR(255) DEFAULT NULL,
  `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `hire_date`   DATE DEFAULT NULL,
  `created_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`account_id`),
  UNIQUE KEY `uniq_email` (`email`),
  KEY `fk_acc_role` (`role_id`),
  KEY `fk_acc_pos` (`position_id`),
  CONSTRAINT `fk_acc_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  CONSTRAINT `fk_acc_pos`  FOREIGN KEY (`position_id`) REFERENCES `positions` (`position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mật khẩu mặc định: md5('123456') = e10adc3949ba59abbe56e057f20f883e
-- admin123  → e10adc3949ba59abbe56e057f20f883e (md5: 123456)
-- Để đơn giản dùng cùng 1 password hash: 123456
INSERT INTO `accounts` (`account_id`,`full_name`,`email`,`password`,`role_id`,`position_id`,`phone`,`status`,`hire_date`) VALUES
(1, 'Admin Hệ Thống',   'admin@eldercoffee.com',     'e10adc3949ba59abbe56e057f20f883e', 1, 1, '0900000001', 'active', '2023-01-01'),
(2, 'Nguyễn Quản Lý',  'manager@eldercoffee.com',   'e10adc3949ba59abbe56e057f20f883e', 2, 2, '0900000002', 'active', '2023-02-01'),
(3, 'Trần Bán Hàng',   'sales@eldercoffee.com',     'e10adc3949ba59abbe56e057f20f883e', 3, 3, '0900000003', 'active', '2023-03-01'),
(4, 'Lê Nhân Viên Kho','warehouse@eldercoffee.com', 'e10adc3949ba59abbe56e057f20f883e', 4, 4, '0900000004', 'active', '2023-03-15'),
(5, 'Phạm Thị Hoa',    'hoa@eldercoffee.com',       'e10adc3949ba59abbe56e057f20f883e', 3, 3, '0900000005', 'active', '2023-04-01'),
(6, 'Ngô Văn Minh',    'minh@eldercoffee.com',      'e10adc3949ba59abbe56e057f20f883e', 4, 4, '0900000006', 'active', '2023-05-01');

-- ============================================================
-- 4. SUPPLIERS (Nhà cung cấp)
-- ============================================================
CREATE TABLE `suppliers` (
  `supplier_id`   INT NOT NULL AUTO_INCREMENT,
  `supplier_code` VARCHAR(50) NOT NULL,
  `supplier_name` VARCHAR(200) NOT NULL,
  `contact_name`  VARCHAR(150) DEFAULT NULL,
  `phone_number`  VARCHAR(20) DEFAULT NULL,
  `email`         VARCHAR(150) DEFAULT NULL,
  `address`       TEXT DEFAULT NULL,
  `status`        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`supplier_id`),
  UNIQUE KEY `uniq_sup_code` (`supplier_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `suppliers` VALUES
(1,'SUP-001','Công ty TNHH Trà Xanh Việt','Nguyễn Văn An','0800000001','traxanh@vn.com','123 Nguyễn Trãi, Q1','active',NOW(),NOW()),
(2,'SUP-002','Nhà cung cấp Cà Phê Đà Lạt','Trần Thị Bình','0800000002','caphe.dalat@vn.com','456 Lê Lợi, Đà Lạt','active',NOW(),NOW()),
(3,'SUP-003','Công ty Sữa Tươi Nam Dương','Lê Hoàng Cường','0800000003','suatuoi@vn.com','789 CMT8, Q3','active',NOW(),NOW()),
(4,'SUP-004','Nhà phân phối Trái Cây Tươi','Phạm Thị Dung','0800000004','traicay@vn.com','321 Đinh Tiên Hoàng, Q.Bình Thạnh','active',NOW(),NOW()),
(5,'SUP-005','Công ty Đường Sucrose VN','Mai Văn Em','0800000005','duong@sucrose.vn','567 Võ Văn Tần, Q3','inactive',NOW(),NOW());

-- ============================================================
-- 5. CATEGORY (Danh mục)
-- ============================================================
CREATE TABLE `category` (
  `category_id`   INT NOT NULL AUTO_INCREMENT,
  `category_name` VARCHAR(100) NOT NULL,
  `created_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `category` (`category_id`,`category_name`) VALUES
(1,'Cà Phê'),
(2,'Trà'),
(3,'Sinh Tố'),
(4,'Nước Ép'),
(5,'Đồ Uống Đá Xay'),
(6,'Bánh & Snack');

-- ============================================================
-- 6. ITEMS (Sản phẩm)
-- ============================================================
CREATE TABLE `items` (
  `item_id`        INT NOT NULL AUTO_INCREMENT,
  `item_name`      VARCHAR(200) NOT NULL,
  `category_id`    INT DEFAULT NULL,
  `description`    TEXT DEFAULT NULL,
  `unit_price`     DECIMAL(10,0) NOT NULL DEFAULT 0,
  `purchase_price` DECIMAL(10,0) NOT NULL DEFAULT 0,
  `stock_quantity` INT NOT NULL DEFAULT 0,
  `item_status`    ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `item_image`     VARCHAR(255) DEFAULT NULL,
  `added_date`     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`item_id`),
  KEY `fk_item_cat` (`category_id`),
  CONSTRAINT `fk_item_cat` FOREIGN KEY (`category_id`) REFERENCES `category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `items` (`item_id`,`item_name`,`category_id`,`description`,`unit_price`,`purchase_price`,`stock_quantity`,`item_status`) VALUES
(1, 'Cà Phê Đen',       1, 'Cà phê đen truyền thống, đậm đà',     25000, 8000,  150, 'active'),
(2, 'Cà Phê Sữa',       1, 'Cà phê sữa đặc thơm ngon',            30000, 10000, 120, 'active'),
(3, 'Bạc Xỉu',          1, 'Cà phê sữa nhiều sữa ít cà phê',       35000, 12000, 80,  'active'),
(4, 'Cà Phê Trứng',     1, 'Cà phê trứng đặc sản Hà Nội',          45000, 15000, 60,  'active'),
(5, 'Trà Đào Cam Sả',   2, 'Trà đào thơm mát với cam và sả',       40000, 12000, 100, 'active'),
(6, 'Trà Chanh Thái',   2, 'Trà chanh dây vị Thái đặc trưng',      38000, 11000, 90,  'active'),
(7, 'Hồng Trà Sữa',     2, 'Hồng trà sữa trân châu đường đen',     45000, 14000, 70,  'active'),
(8, 'Sinh Tố Bơ',       3, 'Sinh tố bơ tươi mịn béo',              55000, 20000, 50,  'active'),
(9, 'Sinh Tố Dâu',      3, 'Sinh tố dâu tươi ngọt mát',            50000, 18000, 45,  'active'),
(10,'Nước Ép Cam Tươi', 4, 'Cam vắt tươi 100% nguyên chất',        40000, 12000, 80,  'active'),
(11,'Đá Xay Matcha',    5, 'Matcha đá xay với kem tươi',            55000, 18000, 60,  'active'),
(12,'Đá Xay Chocolate', 5, 'Chocolate đá xay ngọt ngào',            50000, 16000, 55,  'active'),
(13,'Bánh Mì Bơ Kẹp',   6, 'Bánh mì bơ giòn rụm',                  25000, 10000, 40,  'active'),
(14,'Cookie Socola',    6, 'Cookie socola nhà làm',                  30000, 12000, 35,  'active'),
(15,'Cà Phê Latte',     1, 'Latte sữa hơi mịn thơm',               45000, 15000, 0,   'inactive');

-- ============================================================
-- 7. CUSTOMERS (Khách hàng)
-- ============================================================
CREATE TABLE `customers` (
  `customer_id`   INT NOT NULL AUTO_INCREMENT,
  `customer_name` VARCHAR(150) NOT NULL,
  `phone_number`  VARCHAR(20) DEFAULT NULL,
  `email`         VARCHAR(150) DEFAULT NULL,
  `address`       TEXT DEFAULT NULL,
  `created_at`    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customers` (`customer_id`,`customer_name`,`phone_number`) VALUES
(1,'Nguyễn Thị Lan',     '0901234001'),
(2,'Trần Văn Bình',      '0901234002'),
(3,'Lê Thị Cúc',         '0901234003'),
(4,'Phạm Minh Đức',      '0901234004'),
(5,'Hoàng Thị Hương',    '0901234005'),
(6,'Vũ Quang Khải',      '0901234006'),
(7,'Đặng Thị Linh',      '0901234007'),
(8,'Bùi Văn Mạnh',       '0901234008'),
(9,'Phan Thị Nga',       '0901234009'),
(10,'Đỗ Văn Phúc',       '0901234010');

-- ============================================================
-- 8. INVOICES (Hóa đơn bán hàng)
-- ============================================================
CREATE TABLE `invoices` (
  `invoice_id`    INT NOT NULL AUTO_INCREMENT,
  `account_id`    INT NOT NULL,
  `customer_id`   INT NOT NULL,
  `discount`      DECIMAL(10,0) NOT NULL DEFAULT 0,
  `total`         DECIMAL(12,0) NOT NULL DEFAULT 0,
  `status`        ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'completed',
  `notes`         TEXT DEFAULT NULL,
  `creation_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`invoice_id`),
  KEY `fk_inv_acc` (`account_id`),
  KEY `fk_inv_cust` (`customer_id`),
  KEY `idx_inv_time` (`creation_time`),
  CONSTRAINT `fk_inv_acc`  FOREIGN KEY (`account_id`)  REFERENCES `accounts`  (`account_id`),
  CONSTRAINT `fk_inv_cust` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. INVOICE_DETAILS (Chi tiết hóa đơn)
-- ============================================================
CREATE TABLE `invoice_details` (
  `detail_id`  INT NOT NULL AUTO_INCREMENT,
  `invoice_id` INT NOT NULL,
  `item_id`    INT NOT NULL,
  `quantity`   INT NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,0) NOT NULL DEFAULT 0,
  PRIMARY KEY (`detail_id`),
  KEY `fk_det_inv` (`invoice_id`),
  KEY `fk_det_item` (`item_id`),
  CONSTRAINT `fk_det_inv`  FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`invoice_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_det_item` FOREIGN KEY (`item_id`)    REFERENCES `items`    (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed invoices data
INSERT INTO `invoices` (`invoice_id`,`account_id`,`customer_id`,`discount`,`total`,`status`,`creation_time`) VALUES
(1, 3,1, 0,   95000,  'completed','2025-11-05 09:00:00'),
(2, 3,2, 0,   120000, 'completed','2025-11-10 10:30:00'),
(3, 5,3, 5000,155000, 'completed','2025-11-15 14:00:00'),
(4, 3,4, 0,   75000,  'completed','2025-12-02 09:15:00'),
(5, 5,5, 0,   190000, 'completed','2025-12-08 11:00:00'),
(6, 3,6, 10000,135000,'completed','2025-12-15 15:30:00'),
(7, 5,7, 0,   215000, 'completed','2026-01-05 09:00:00'),
(8, 3,8, 0,   95000,  'completed','2026-01-12 10:00:00'),
(9, 5,9, 0,   165000, 'completed','2026-01-20 14:00:00'),
(10,3,10,0,   110000, 'completed','2026-02-03 09:30:00'),
(11,5,1, 5000,145000, 'completed','2026-02-10 11:00:00'),
(12,3,2, 0,   200000, 'completed','2026-02-18 13:00:00'),
(13,5,3, 0,   132000, 'completed','2026-03-01 09:00:00'),
(14,3,4, 0,   175000, 'completed','2026-03-10 10:30:00'),
(15,5,5, 0,   260000, 'completed','2026-03-20 15:00:00');

INSERT INTO `invoice_details` (`invoice_id`,`item_id`,`quantity`,`unit_price`) VALUES
(1, 1, 2, 25000),(1, 5, 1, 40000),(1, 13,1, 25000),
(2, 2, 2, 30000),(2, 7, 1, 45000),(2, 11,1, 15000),
(3, 4, 1, 45000),(3, 8, 2, 55000),(3, 14,1, 30000),
(4, 1, 3, 25000),
(5, 3, 2, 35000),(5, 9, 2, 50000),(5, 12,1, 50000),
(6, 5, 2, 40000),(6, 6, 1, 38000),(6, 13,1, 25000),
(7, 4, 2, 45000),(7, 7, 2, 45000),(7, 11,1, 55000),(7,14,1,30000),
(8, 2, 2, 30000),(8, 13,1,25000),(8,10,1,40000),  
(9, 8, 1, 55000),(9, 9, 1, 50000),(9, 3, 1,35000),(9,6,1,38000),
(10,1, 2,25000),(10,7,1,45000),(10,14,1,30000),
(11,5,2,40000),(11,11,1,55000),(11,4,1,45000),
(12,3,2,35000),(12,8,2,55000),(12,12,1,50000),
(13,2,2,30000),(13,6,2,38000),(13,10,1,40000),
(14,4,2,45000),(14,9,1,50000),(14,13,2,25000),
(15,7,2,45000),(15,8,2,55000),(15,11,2,55000),(15,4,1,45000);

-- ============================================================
-- 10. INVENTORY_RECEIPTS (Phiếu nhập kho)
-- ============================================================
CREATE TABLE `inventory_receipts` (
  `receipt_id`   INT NOT NULL AUTO_INCREMENT,
  `receipt_code` VARCHAR(50) NOT NULL,
  `supplier_id`  INT NOT NULL,
  `import_date`  DATE NOT NULL,
  `total_value`  DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `note`         TEXT DEFAULT NULL,
  `status`       ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'completed',
  `created_by`   INT NOT NULL,
  `created_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`receipt_id`),
  UNIQUE KEY `uniq_receipt_code` (`receipt_code`),
  KEY `idx_receipt_date` (`import_date`),
  KEY `fk_receipt_sup` (`supplier_id`),
  KEY `fk_receipt_by` (`created_by`),
  CONSTRAINT `fk_receipt_sup` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`supplier_id`),
  CONSTRAINT `fk_receipt_by`  FOREIGN KEY (`created_by`)  REFERENCES `accounts` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. INVENTORY_RECEIPT_ITEMS (Chi tiết phiếu nhập)
-- ============================================================
CREATE TABLE `inventory_receipt_items` (
  `receipt_item_id` INT NOT NULL AUTO_INCREMENT,
  `receipt_id`      INT NOT NULL,
  `item_id`         INT NOT NULL,
  `quantity`        INT NOT NULL,
  `import_price`    DECIMAL(10,2) NOT NULL,
  `unit_price`      DECIMAL(10,2) NOT NULL DEFAULT 0,
  `line_total`      DECIMAL(14,2) NOT NULL,
  PRIMARY KEY (`receipt_item_id`),
  KEY `fk_ri_receipt` (`receipt_id`),
  KEY `fk_ri_item` (`item_id`),
  CONSTRAINT `fk_ri_receipt` FOREIGN KEY (`receipt_id`) REFERENCES `inventory_receipts`(`receipt_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ri_item`    FOREIGN KEY (`item_id`)    REFERENCES `items`(`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed receipts
INSERT INTO `inventory_receipts` (`receipt_id`,`receipt_code`,`supplier_id`,`import_date`,`total_value`,`note`,`status`,`created_by`) VALUES
(1,'PN-20251101-001',1,'2025-11-01',3600000,'Nhập hàng đầu tháng 11','completed',4),
(2,'PN-20251201-001',2,'2025-12-01',4200000,'Nhập cà phê tháng 12','completed',4),
(3,'PN-20260101-001',3,'2026-01-05',3000000,'Nhập sữa đầu tháng','completed',6),
(4,'PN-20260201-001',1,'2026-02-03',2400000,'Bổ sung trà các loại','completed',4),
(5,'PN-20260301-001',4,'2026-03-01',2700000,'Nhập trái cây làm sinh tố','completed',6);

INSERT INTO `inventory_receipt_items` (`receipt_id`,`item_id`,`quantity`,`import_price`,`unit_price`,`line_total`) VALUES
(1,5,100,12000,40000,1200000),(1,6,100,11000,38000,1100000),(1,7,100,14000,45000,1400000),
(2,1,150,8000,25000,1200000),(2,2,150,10000,30000,1500000),(2,3,100,12000,35000,1200000),(2,4,50,15000,45000,750000),
(3,8,60,20000,55000,1200000),(3,9,60,18000,50000,1080000),(3,12,60,16000,50000,960000),
(4,5,80,12000,40000,960000),(4,6,80,11000,38000,880000),(4,7,60,14000,45000,840000),
(5,8,50,20000,55000,1000000),(5,9,50,18000,50000,900000),(5,10,80,12000,40000,960000);

-- ============================================================
-- 12. INVENTORY_EXPORTS (Phiếu xuất kho)
-- ============================================================
CREATE TABLE `inventory_exports` (
  `export_id`   INT NOT NULL AUTO_INCREMENT,
  `export_code` VARCHAR(50) NOT NULL,
  `export_date` DATE NOT NULL,
  `reason`      VARCHAR(200) DEFAULT 'bán hàng',
  `total_value` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `note`        TEXT DEFAULT NULL,
  `created_by`  INT NOT NULL,
  `created_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`export_id`),
  UNIQUE KEY `uniq_export_code` (`export_code`),
  KEY `idx_exports_date` (`export_date`),
  KEY `fk_exports_by` (`created_by`),
  CONSTRAINT `fk_exports_created_by` FOREIGN KEY (`created_by`) REFERENCES `accounts` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. INVENTORY_EXPORT_ITEMS (Chi tiết phiếu xuất)
-- ============================================================
CREATE TABLE `inventory_export_items` (
  `export_item_id` INT NOT NULL AUTO_INCREMENT,
  `export_id`      INT NOT NULL,
  `item_id`        INT NOT NULL,
  `quantity`       INT NOT NULL,
  `unit_price`     DECIMAL(10,2) NOT NULL,
  `purchase_price` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `line_total`     DECIMAL(14,2) NOT NULL,
  PRIMARY KEY (`export_item_id`),
  KEY `fk_ei_export` (`export_id`),
  KEY `fk_ei_item`   (`item_id`),
  CONSTRAINT `fk_export_items_export` FOREIGN KEY (`export_id`) REFERENCES `inventory_exports`(`export_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_export_items_item`   FOREIGN KEY (`item_id`)   REFERENCES `items`(`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 14. STOCK_MOVEMENTS (Lịch sử biến động kho)
-- ============================================================
CREATE TABLE `stock_movements` (
  `movement_id`    INT NOT NULL AUTO_INCREMENT,
  `item_id`        INT NOT NULL,
  `movement_type`  ENUM('import','export','adjustment','receipt_update','receipt_delete','export_update','export_delete') NOT NULL,
  `quantity_change` INT NOT NULL,
  `stock_before`   INT NOT NULL,
  `stock_after`    INT NOT NULL,
  `unit_cost`      DECIMAL(10,2) NOT NULL DEFAULT 0,
  `reference_type` VARCHAR(50) DEFAULT NULL,
  `reference_id`   INT DEFAULT NULL,
  `note`           TEXT DEFAULT NULL,
  `created_by`     INT NOT NULL,
  `created_at`     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`movement_id`),
  KEY `fk_mv_item` (`item_id`),
  KEY `fk_mv_by`   (`created_by`),
  CONSTRAINT `fk_mv_item` FOREIGN KEY (`item_id`)    REFERENCES `items`(`item_id`),
  CONSTRAINT `fk_mv_by`   FOREIGN KEY (`created_by`) REFERENCES `accounts`(`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 15. EMPLOYEE_POSITIONS_HISTORY (Lịch sử chức vụ)
-- ============================================================
CREATE TABLE `employee_positions_history` (
  `history_id`  INT NOT NULL AUTO_INCREMENT,
  `account_id`  INT NOT NULL,
  `position_id` INT NOT NULL,
  `start_date`  DATE NOT NULL,
  `end_date`    DATE DEFAULT NULL,
  `reason`      TEXT DEFAULT NULL,
  `created_by`  INT DEFAULT NULL,
  `created_at`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`history_id`),
  KEY `fk_eph_acc` (`account_id`),
  KEY `fk_eph_pos` (`position_id`),
  CONSTRAINT `fk_eph_acc` FOREIGN KEY (`account_id`)  REFERENCES `accounts`  (`account_id`),
  CONSTRAINT `fk_eph_pos` FOREIGN KEY (`position_id`) REFERENCES `positions` (`position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `employee_positions_history` (`account_id`,`position_id`,`start_date`,`end_date`,`reason`) VALUES
(2, 2, '2023-02-01', NULL, 'Nhận chức vụ ban đầu'),
(3, 6, '2023-03-01', '2023-06-30', 'Thử việc'),
(3, 3, '2023-07-01', NULL, 'Chính thức'),
(4, 6, '2023-03-15', '2023-09-14', 'Thử việc'),
(4, 4, '2023-09-15', NULL, 'Chính thức'),
(5, 3, '2023-04-01', NULL, 'Nhận chức vụ ban đầu'),
(6, 4, '2023-05-01', NULL, 'Nhận chức vụ ban đầu');

-- ============================================================
-- 16. SALARY_RECORDS (Bảng lương)
-- ============================================================
CREATE TABLE `salary_records` (
  `salary_record_id` INT NOT NULL AUTO_INCREMENT,
  `account_id`       INT NOT NULL,
  `position_id`      INT NOT NULL,
  `salary_month`     TINYINT NOT NULL,
  `salary_year`      YEAR NOT NULL,
  `base_salary`      BIGINT NOT NULL DEFAULT 0,
  `allowance`        BIGINT NOT NULL DEFAULT 0,
  `bonus`            BIGINT NOT NULL DEFAULT 0,
  `deductions`       BIGINT NOT NULL DEFAULT 0,
  `total_salary`     BIGINT NOT NULL DEFAULT 0,
  `notes`            TEXT DEFAULT NULL,
  `created_by`       INT DEFAULT NULL,
  `created_at`       TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`salary_record_id`),
  UNIQUE KEY `uniq_salary` (`account_id`,`salary_month`,`salary_year`),
  KEY `fk_sr_acc` (`account_id`),
  KEY `fk_sr_pos` (`position_id`),
  CONSTRAINT `fk_sr_acc` FOREIGN KEY (`account_id`)  REFERENCES `accounts`  (`account_id`),
  CONSTRAINT `fk_sr_pos` FOREIGN KEY (`position_id`) REFERENCES `positions` (`position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed lương tháng 1,2,3/2026 cho các nhân viên
INSERT INTO `salary_records` (`account_id`,`position_id`,`salary_month`,`salary_year`,`base_salary`,`allowance`,`bonus`,`deductions`,`total_salary`,`notes`,`created_by`) VALUES
(2,2,1,2026,15000000,2000000,1000000,0,  18000000,'Tháng 1/2026',1),
(3,3,1,2026,8000000, 500000, 500000, 0,   9000000,'Tháng 1/2026',2),
(4,4,1,2026,7500000, 500000, 0,      0,   8000000,'Tháng 1/2026',2),
(5,3,1,2026,8000000, 500000, 200000, 0,   8700000,'Tháng 1/2026',2),
(6,4,1,2026,7500000, 500000, 0,      0,   8000000,'Tháng 1/2026',2),
(2,2,2,2026,15000000,2000000,0,      0,  17000000,'Tháng 2/2026',1),
(3,3,2,2026,8000000, 500000, 0,      0,   8500000,'Tháng 2/2026',2),
(4,4,2,2026,7500000, 500000, 0,      0,   8000000,'Tháng 2/2026',2),
(5,3,2,2026,8000000, 500000, 0,      0,   8500000,'Tháng 2/2026',2),
(6,4,2,2026,7500000, 500000, 0,      0,   8000000,'Tháng 2/2026',2),
(2,2,3,2026,15000000,2000000,2000000,0,  19000000,'Thưởng quý 1',1),
(3,3,3,2026,8000000, 500000, 1000000,0,   9500000,'Thưởng quý 1',2),
(4,4,3,2026,7500000, 500000, 500000, 0,   8500000,'Thưởng quý 1',2),
(5,3,3,2026,8000000, 500000, 1000000,0,   9500000,'Thưởng quý 1',2),
(6,4,3,2026,7500000, 500000, 500000, 0,   8500000,'Thưởng quý 1',2);

-- ============================================================
-- 17. LEAVE_REQUESTS (Đơn nghỉ phép)
-- ============================================================
CREATE TABLE `leave_requests` (
  `leave_request_id` INT NOT NULL AUTO_INCREMENT,
  `account_id`       INT NOT NULL,
  `from_date`        DATE NOT NULL,
  `to_date`          DATE NOT NULL,
  `leave_type`       ENUM('phép','bệnh','thai sản','không lương','khác') NOT NULL DEFAULT 'phép',
  `reason`           TEXT NOT NULL,
  `status`           ENUM('chờ duyệt','chấp thuận','từ chối','hủy') NOT NULL DEFAULT 'chờ duyệt',
  `approved_by`      INT DEFAULT NULL,
  `approved_at`      DATETIME DEFAULT NULL,
  `notes`            TEXT DEFAULT NULL,
  `created_at`       TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`leave_request_id`),
  KEY `fk_lr_acc` (`account_id`),
  KEY `fk_lr_appr` (`approved_by`),
  CONSTRAINT `fk_lr_acc`  FOREIGN KEY (`account_id`) REFERENCES `accounts`(`account_id`),
  CONSTRAINT `fk_lr_appr` FOREIGN KEY (`approved_by`) REFERENCES `accounts`(`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `leave_requests` (`account_id`,`from_date`,`to_date`,`leave_type`,`reason`,`status`,`approved_by`,`approved_at`) VALUES
(3,'2026-02-10','2026-02-10','phép',   'Việc gia đình',   'chấp thuận',2,NOW()),
(5,'2026-02-20','2026-02-21','bệnh',   'Bị ốm sốt cao',   'chấp thuận',2,NOW()),
(4,'2026-03-05','2026-03-05','phép',   'Đám cưới người thân','chấp thuận',2,NOW()),
(6,'2026-03-15','2026-03-15','phép',   'Việc riêng',      'từ chối',   2,NOW()),
(3,'2026-04-08','2026-04-09','phép',   'Du lịch gia đình','chờ duyệt', NULL,NULL),
(5,'2026-04-10','2026-04-10','bệnh',   'Đau đầu chóng mặt','chờ duyệt',NULL,NULL);

-- ============================================================
-- 18. RESIGNATION_REQUESTS (Đơn nghỉ việc)
-- ============================================================
CREATE TABLE `resignation_requests` (
  `resignation_request_id` INT NOT NULL AUTO_INCREMENT,
  `account_id`             INT NOT NULL,
  `notice_date`            DATE NOT NULL,
  `effective_date`         DATE NOT NULL,
  `reason`                 TEXT NOT NULL,
  `status`                 ENUM('chờ duyệt','chấp thuận','từ chối','hủy') NOT NULL DEFAULT 'chờ duyệt',
  `approved_by`            INT DEFAULT NULL,
  `approved_at`            DATETIME DEFAULT NULL,
  `notes`                  TEXT DEFAULT NULL,
  `created_at`             TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`             TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`resignation_request_id`),
  KEY `fk_rr_acc`  (`account_id`),
  KEY `fk_rr_appr` (`approved_by`),
  CONSTRAINT `fk_rr_acc`  FOREIGN KEY (`account_id`) REFERENCES `accounts`(`account_id`),
  CONSTRAINT `fk_rr_appr` FOREIGN KEY (`approved_by`) REFERENCES `accounts`(`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 19. SYSTEM_CONFIG (Cấu hình hệ thống)
-- ============================================================
CREATE TABLE `system_config` (
  `config_key`   VARCHAR(100) NOT NULL,
  `config_value` TEXT DEFAULT NULL,
  `description`  VARCHAR(255) DEFAULT NULL,
  `updated_at`   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `system_config` VALUES
('company_name',      'ElderCoffee',                    'Tên công ty', NOW()),
('company_address',   '123 Đường Cà Phê, Q.1, TP.HCM', 'Địa chỉ công ty', NOW()),
('company_phone',     '028-1234-5678',                  'Số điện thoại', NOW()),
('company_email',     'info@eldercoffee.com',            'Email công ty', NOW()),
('tax_code',          '0123456789',                     'Mã số thuế', NOW()),
('low_stock_threshold','10',                            'Ngưỡng tồn kho thấp', NOW()),
('default_salary_allowance','500000',                   'Phụ cấp mặc định', NOW());

-- ============================================================
-- 20. ACTIVITY_LOGS (Nhật ký hoạt động)
-- ============================================================
CREATE TABLE `activity_logs` (
  `log_id`     INT NOT NULL AUTO_INCREMENT,
  `account_id` INT DEFAULT NULL,
  `action`     VARCHAR(100) NOT NULL,
  `module`     VARCHAR(50) NOT NULL,
  `detail`     TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `fk_log_acc` (`account_id`),
  KEY `idx_log_module` (`module`),
  CONSTRAINT `fk_log_acc` FOREIGN KEY (`account_id`) REFERENCES `accounts`(`account_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- STORED PROCEDURES & FUNCTIONS
-- ============================================================
DELIMITER //

-- Lấy hóa đơn theo khoảng ngày
DROP PROCEDURE IF EXISTS `GetInvoicesByDateRange`//
CREATE PROCEDURE `GetInvoicesByDateRange`(IN p_start DATE, IN p_end DATE)
BEGIN
  SELECT iv.invoice_id, iv.creation_time, ac.full_name, ct.customer_name, iv.total
  FROM invoices iv
  LEFT JOIN accounts ac  ON iv.account_id  = ac.account_id
  LEFT JOIN customers ct ON iv.customer_id = ct.customer_id
  WHERE DATE(iv.creation_time) BETWEEN p_start AND p_end
  ORDER BY iv.creation_time DESC;
END//

-- Thống kê doanh thu theo tháng
DROP PROCEDURE IF EXISTS `GetMonthlyRevenue`//
CREATE PROCEDURE `GetMonthlyRevenue`(IN p_year INT)
BEGIN
  SELECT 
    MONTH(creation_time) AS thang,
    COUNT(*) AS so_hoa_don,
    SUM(total) AS doanh_thu,
    SUM(discount) AS tong_giam_gia
  FROM invoices
  WHERE YEAR(creation_time) = p_year AND status != 'cancelled'
  GROUP BY MONTH(creation_time)
  ORDER BY thang;
END//

-- Thống kê lợi nhuận theo tháng
DROP PROCEDURE IF EXISTS `GetMonthlyProfit`//
CREATE PROCEDURE `GetMonthlyProfit`(IN p_year INT, IN p_month INT)
BEGIN
  DECLARE v_revenue DECIMAL(14,2);
  DECLARE v_cost DECIMAL(14,2);
  
  SELECT COALESCE(SUM(iv.total),0) INTO v_revenue
  FROM invoices iv
  WHERE YEAR(iv.creation_time) = p_year 
    AND (p_month = 0 OR MONTH(iv.creation_time) = p_month)
    AND iv.status != 'cancelled';
  
  SELECT COALESCE(SUM(id.quantity * i.purchase_price),0) INTO v_cost
  FROM invoice_details id
  JOIN invoices iv ON iv.invoice_id = id.invoice_id
  JOIN items i ON i.item_id = id.item_id
  WHERE YEAR(iv.creation_time) = p_year
    AND (p_month = 0 OR MONTH(iv.creation_time) = p_month)
    AND iv.status != 'cancelled';
  
  SELECT v_revenue AS doanh_thu, v_cost AS chi_phi, (v_revenue - v_cost) AS loi_nhuan;
END//

-- Tính lương nhân viên
DROP PROCEDURE IF EXISTS `CalculateEmployeeSalary`//
CREATE PROCEDURE `CalculateEmployeeSalary`(
  IN p_account_id INT, IN p_month INT, IN p_year INT,
  IN p_allowance BIGINT, IN p_bonus BIGINT, IN p_deductions BIGINT,
  IN p_created_by INT, IN p_notes TEXT
)
BEGIN
  DECLARE v_position_id INT;
  DECLARE v_base_salary BIGINT;
  DECLARE v_total BIGINT;
  DECLARE v_exists INT;
  
  SELECT a.position_id, p.base_salary INTO v_position_id, v_base_salary
  FROM accounts a JOIN positions p ON p.position_id = a.position_id
  WHERE a.account_id = p_account_id;
  
  SET v_total = v_base_salary + p_allowance + p_bonus - p_deductions;
  
  SELECT COUNT(*) INTO v_exists FROM salary_records
  WHERE account_id = p_account_id AND salary_month = p_month AND salary_year = p_year;
  
  IF v_exists > 0 THEN
    UPDATE salary_records SET
      base_salary = v_base_salary, allowance = p_allowance, bonus = p_bonus,
      deductions = p_deductions, total_salary = v_total, notes = p_notes,
      created_by = p_created_by, updated_at = NOW()
    WHERE account_id = p_account_id AND salary_month = p_month AND salary_year = p_year;
  ELSE
    INSERT INTO salary_records (account_id, position_id, salary_month, salary_year,
      base_salary, allowance, bonus, deductions, total_salary, notes, created_by)
    VALUES (p_account_id, v_position_id, p_month, p_year,
      v_base_salary, p_allowance, p_bonus, p_deductions, v_total, p_notes, p_created_by);
  END IF;
  
  SELECT v_base_salary AS base_salary, p_allowance AS allowance,
         p_bonus AS bonus, p_deductions AS deductions, v_total AS total_salary;
END//

DELIMITER ;

-- ============================================================
-- VIEWS tiện ích
-- ============================================================

-- View thống kê kho hiện tại
CREATE OR REPLACE VIEW `v_stock_summary` AS
SELECT 
  i.item_id, i.item_name, c.category_name,
  i.stock_quantity, i.purchase_price, i.unit_price,
  (i.stock_quantity * i.purchase_price) AS stock_cost_value,
  (i.stock_quantity * i.unit_price) AS stock_sell_value,
  i.item_status
FROM items i
LEFT JOIN category c ON c.category_id = i.category_id
WHERE i.item_status = 'active';

-- View doanh thu theo tháng/năm
CREATE OR REPLACE VIEW `v_monthly_revenue` AS
SELECT
  YEAR(creation_time) AS nam,
  MONTH(creation_time) AS thang,
  COUNT(*) AS so_hoa_don,
  SUM(total) AS doanh_thu,
  SUM(discount) AS tong_giam_gia
FROM invoices
WHERE status != 'cancelled'
GROUP BY YEAR(creation_time), MONTH(creation_time);

-- View lương nhân viên
CREATE OR REPLACE VIEW `v_salary_report` AS
SELECT
  sr.salary_year, sr.salary_month,
  a.account_id, a.full_name, r.display_name AS role_name,
  p.position_name, sr.base_salary,
  sr.allowance, sr.bonus, sr.deductions, sr.total_salary, sr.notes
FROM salary_records sr
JOIN accounts a ON a.account_id = sr.account_id
JOIN roles r ON r.id = a.role_id
JOIN positions p ON p.position_id = sr.position_id;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- HƯỚNG DẪN BACKUP & RESTORE
-- ============================================================
-- BACKUP (chạy từ command line):
--   mysqldump -u root -p eldercoffee_db > backup_YYYYMMDD.sql
--
-- RESTORE:
--   mysql -u root -p eldercoffee_db < backup_YYYYMMDD.sql
--
-- Hoặc dùng phpMyAdmin:
--   Xuất: Database > Export > Quick > Go
--   Nhập: Database > Import > Chọn file .sql > Go
-- ============================================================
