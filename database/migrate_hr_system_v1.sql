-- =====================================================
-- HR MANAGEMENT SYSTEM - DATABASE MIGRATION v1
-- =====================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Table: positions (Chức vụ trong công ty)
DROP TABLE IF EXISTS `positions`;
CREATE TABLE `positions` (
  `position_id` int NOT NULL AUTO_INCREMENT,
  `position_name` varchar(100) NOT NULL,
  `base_salary` bigint NOT NULL DEFAULT 0,
  `description` text,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`position_id`),
  UNIQUE KEY `uniq_position_name` (`position_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed positions
INSERT INTO `positions` (position_name, base_salary, description, is_active) VALUES
('Quản lý', 15000000, 'Quản lý chi nhánh hoặc bộ phận', 1),
('Nhân viên bán hàng', 8000000, 'Bán hàng trực tiếp tại quầy', 1),
('Nhân viên kho', 7500000, 'Quản lý kho hàng', 1),
('Giám đốc', 25000000, 'Giám đốc công ty', 1),
('Nhân viên kế toán', 9000000, 'Xử lý các vấn đề tài chính', 1),
('Nhân viên HR', 8500000, 'Quản lý nhân sự', 1);

-- Table: employee_positions_history (Lịch sử chức vụ của nhân viên)
DROP TABLE IF EXISTS `employee_positions_history`;
CREATE TABLE `employee_positions_history` (
  `history_id` int NOT NULL AUTO_INCREMENT,
  `account_id` int NOT NULL,
  `position_id` int NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date,
  `reason` varchar(255),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`history_id`),
  KEY `fk_eph_account` (`account_id`),
  KEY `fk_eph_position` (`position_id`),
  CONSTRAINT `fk_eph_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_eph_position` FOREIGN KEY (`position_id`) REFERENCES `positions` (`position_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: salary_records (Bản ghi lương hàng tháng)
DROP TABLE IF EXISTS `salary_records`;
CREATE TABLE `salary_records` (
  `salary_record_id` int NOT NULL AUTO_INCREMENT,
  `account_id` int NOT NULL,
  `position_id` int NOT NULL,
  `salary_month` int NOT NULL,
  `salary_year` int NOT NULL,
  `base_salary` bigint NOT NULL,
  `allowance` bigint DEFAULT 0,
  `bonus` bigint DEFAULT 0,
  `deductions` bigint DEFAULT 0,
  `total_salary` bigint NOT NULL,
  `notes` varchar(255),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`salary_record_id`),
  KEY `fk_sr_account` (`account_id`),
  KEY `fk_sr_position` (`position_id`),
  UNIQUE KEY `uniq_salary_record` (`account_id`, `salary_month`, `salary_year`),
  CONSTRAINT `fk_sr_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sr_position` FOREIGN KEY (`position_id`) REFERENCES `positions` (`position_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: leave_requests (Yêu cầu nghỉ phép)
DROP TABLE IF EXISTS `leave_requests`;
CREATE TABLE `leave_requests` (
  `leave_request_id` int NOT NULL AUTO_INCREMENT,
  `account_id` int NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `leave_type` enum('phép', 'bệnh', 'không lương') DEFAULT 'phép',
  `reason` text NOT NULL,
  `status` enum('chờ duyệt', 'chấp thuận', 'từ chối', 'hủy') DEFAULT 'chờ duyệt',
  `approved_by` int,
  `approved_at` datetime,
  `notes` varchar(255),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`leave_request_id`),
  KEY `fk_lr_account` (`account_id`),
  KEY `fk_lr_approved_by` (`approved_by`),
  CONSTRAINT `fk_lr_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lr_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `accounts` (`account_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: resignation_requests (Yêu cầu nghỉ việc)
DROP TABLE IF EXISTS `resignation_requests`;
CREATE TABLE `resignation_requests` (
  `resignation_request_id` int NOT NULL AUTO_INCREMENT,
  `account_id` int NOT NULL,
  `notice_date` date NOT NULL,
  `effective_date` date NOT NULL,
  `reason` text NOT NULL,
  `status` enum('chờ duyệt', 'chấp thuận', 'từ chối', 'hủy') DEFAULT 'chờ duyệt',
  `approved_by` int,
  `approved_at` datetime,
  `notes` varchar(255),
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`resignation_request_id`),
  KEY `fk_rr_account` (`account_id`),
  KEY `fk_rr_approved_by` (`approved_by`),
  CONSTRAINT `fk_rr_account` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`account_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rr_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `accounts` (`account_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add position_id column to accounts table if not exists
ALTER TABLE accounts MODIFY position_id INT DEFAULT NULL;
ALTER TABLE accounts ADD CONSTRAINT fk_accounts_position FOREIGN KEY (position_id) REFERENCES positions(position_id) ON DELETE SET NULL;

-- Initial position assignment for existing accounts
UPDATE accounts SET position_id = 2 WHERE role_id = 3;
UPDATE accounts SET position_id = 3 WHERE role_id = 4;
UPDATE accounts SET position_id = 1 WHERE role_id = 2;
UPDATE accounts SET position_id = 4 WHERE role_id = 1;

-- Insert initial position history for existing employees
INSERT IGNORE INTO employee_positions_history (account_id, position_id, start_date) 
SELECT account_id, position_id, DATE(NOW()) FROM accounts WHERE position_id IS NOT NULL;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================
SELECT 'Positions' as table_name, COUNT(*) as count FROM positions
UNION ALL
SELECT 'Employee Position History', COUNT(*) FROM employee_positions_history
UNION ALL
SELECT 'Accounts with Position', COUNT(*) FROM accounts WHERE position_id IS NOT NULL;
