-- =============================================
-- HUMAN RESOURCES MANAGEMENT SYSTEM
-- Database Schema & Migration
-- =============================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- =============================================
-- 1. POSITIONS/JOB TITLES
-- =============================================
DROP TABLE IF EXISTS `positions`;
CREATE TABLE `positions` (
  `position_id` INT NOT NULL AUTO_INCREMENT,
  `position_name` VARCHAR(255) NOT NULL UNIQUE,
  `description` TEXT NULL,
  `base_salary` DECIMAL(12, 2) NOT NULL DEFAULT 0,
  `bonus_percentage` DECIMAL(5, 2) NOT NULL DEFAULT 0,
  `allowance` DECIMAL(12, 2) NOT NULL DEFAULT 0,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 2. STAFF POSITION HISTORY
-- =============================================
DROP TABLE IF EXISTS `staff_position_history`;
CREATE TABLE `staff_position_history` (
  `history_id` INT NOT NULL AUTO_INCREMENT,
  `account_id` INT NOT NULL,
  `position_id` INT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NULL,
  `notes` TEXT NULL,
  `created_by` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`history_id`),
  KEY `idx_account_id` (`account_id`),
  KEY `idx_position_id` (`position_id`),
  CONSTRAINT `fk_staff_pos_hist_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_staff_pos_hist_position` FOREIGN KEY (`position_id`) REFERENCES `positions` (`position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 3. SALARY CALCULATION COMPONENTS
-- =============================================
DROP TABLE IF EXISTS `salary_components`;
CREATE TABLE `salary_components` (
  `component_id` INT NOT NULL AUTO_INCREMENT,
  `component_name` VARCHAR(100) NOT NULL,
  `component_type` ENUM('ADDITION', 'DEDUCTION') NOT NULL,
  `calculation_method` VARCHAR(50) NOT NULL COMMENT 'PERCENTAGE, FIXED, DAYS_BASED',
  `formula` TEXT NULL COMMENT 'Optional formula for dynamic calculation',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`component_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 4. MONTHLY SALARY RECORDS
-- =============================================
DROP TABLE IF EXISTS `salary_records`;
CREATE TABLE `salary_records` (
  `salary_record_id` INT NOT NULL AUTO_INCREMENT,
  `account_id` INT NOT NULL,
  `year` INT NOT NULL,
  `month` INT NOT NULL,
  `position_id` INT NOT NULL,
  `base_salary` DECIMAL(12, 2) NOT NULL,
  `bonus_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0,
  `allowance_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0,
  `deduction_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0,
  `notes` TEXT NULL,
  `status` ENUM('draft', 'approved', 'paid') NOT NULL DEFAULT 'draft',
  `approved_by` INT NULL,
  `approved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`salary_record_id`),
  UNIQUE KEY `unique_staff_salary` (`account_id`, `year`, `month`),
  KEY `idx_account_id` (`account_id`),
  KEY `idx_year_month` (`year`, `month`),
  CONSTRAINT `fk_salary_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_salary_position` FOREIGN KEY (`position_id`) REFERENCES `positions` (`position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 5. LEAVE REQUESTS
-- =============================================
DROP TABLE IF EXISTS `leave_requests`;
CREATE TABLE `leave_requests` (
  `leave_request_id` INT NOT NULL AUTO_INCREMENT,
  `account_id` INT NOT NULL,
  `leave_type` ENUM('annual', 'sick', 'unpaid', 'other') NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `days` INT NOT NULL,
  `reason` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
  `approved_by` INT NULL,
  `approval_notes` TEXT NULL,
  `approved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`leave_request_id`),
  KEY `idx_account_id` (`account_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_leave_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_leave_approver` FOREIGN KEY (`approved_by`) REFERENCES `accounts` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 6. RESIGNATION REQUESTS
-- =============================================
DROP TABLE IF EXISTS `resignation_requests`;
CREATE TABLE `resignation_requests` (
  `resignation_id` INT NOT NULL AUTO_INCREMENT,
  `account_id` INT NOT NULL,
  `resignation_date` DATE NOT NULL,
  `reason` TEXT NOT NULL,
  `notice_days` INT NOT NULL DEFAULT 30,
  `status` ENUM('pending', 'approved', 'rejected', 'processed') NOT NULL DEFAULT 'pending',
  `approved_by` INT NULL,
  `approval_notes` TEXT NULL,
  `approved_at` TIMESTAMP NULL,
  `processed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`resignation_id`),
  KEY `idx_account_id` (`account_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_resignation_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_resignation_approver` FOREIGN KEY (`approved_by`) REFERENCES `accounts` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 7. GENERAL REQUESTS (Extensible)
-- =============================================
DROP TABLE IF EXISTS `requests`;
CREATE TABLE `requests` (
  `request_id` INT NOT NULL AUTO_INCREMENT,
  `account_id` INT NOT NULL,
  `request_type` VARCHAR(50) NOT NULL COMMENT 'TRAINING, ADVANCE, EQUIPMENT, OTHER',
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `amount` DECIMAL(12, 2) NULL COMMENT 'For financial requests',
  `status` ENUM('pending', 'approved', 'rejected', 'completed') NOT NULL DEFAULT 'pending',
  `approved_by` INT NULL,
  `approval_notes` TEXT NULL,
  `approved_at` TIMESTAMP NULL,
  `completed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`request_id`),
  KEY `idx_account_id` (`account_id`),
  KEY `idx_request_type` (`request_type`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_request_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_request_approver` FOREIGN KEY (`approved_by`) REFERENCES `accounts` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 8. SEED DATA - POSITIONS
-- =============================================
INSERT INTO `positions` (position_name, description, base_salary, bonus_percentage, allowance) VALUES
('Quản Lý Kho', 'Quản lý kho và vật tư', 8000000.00, 10, 500000.00),
('Nhân Viên Bán Hàng', 'Bán hàng và tư vấn khách hàng', 5000000.00, 15, 300000.00),
('Giám Đốc', 'Quản lý điều hành công ty', 15000000.00, 20, 1000000.00),
('Kế Toán', 'Quản lý tài chính kế toán', 7000000.00, 8, 400000.00),
('Quản Lý Bán Hàng', 'Quản lý đội ngũ bán hàng', 10000000.00, 12, 600000.00);

-- =============================================
-- 9. SEED DATA - SALARY COMPONENTS
-- =============================================
INSERT INTO `salary_components` (component_name, component_type, calculation_method) VALUES
('Lương Cơ Bản', 'ADDITION', 'FIXED'),
('Thưởng Hiệu Suất', 'ADDITION', 'PERCENTAGE'),
('Phụ Cấp', 'ADDITION', 'FIXED'),
('Bảo Hiểm Xã Hội', 'DEDUCTION', 'PERCENTAGE'),
('Bảo Hiểm Y Tế', 'DEDUCTION', 'PERCENTAGE'),
('Trừ Phép', 'DEDUCTION', 'DAYS_BASED'),
('Trừ Vắng Không Phép', 'DEDUCTION', 'DAYS_BASED');
