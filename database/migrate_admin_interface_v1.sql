-- =============================================
-- Migration for Admin interface
-- Adds suppliers table and item_status column
-- =============================================

USE eldercoffee_db;

CREATE TABLE IF NOT EXISTS suppliers (
  supplier_id INT NOT NULL AUTO_INCREMENT,
  supplier_code VARCHAR(50) NOT NULL,
  supplier_name VARCHAR(255) NOT NULL,
  contact_name VARCHAR(255) DEFAULT NULL,
  phone_number VARCHAR(20) DEFAULT NULL,
  email VARCHAR(255) DEFAULT NULL,
  address VARCHAR(255) DEFAULT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (supplier_id),
  UNIQUE KEY uniq_supplier_code (supplier_code),
  UNIQUE KEY uniq_supplier_name (supplier_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO suppliers (supplier_code, supplier_name, contact_name, phone_number, email, address, status)
SELECT 'SUP-001', 'Công ty CP Nguyên Liệu Sạch', 'Nguyễn Văn A', '0909123456', 'contact@nguyenlieusach.vn', 'Cần Thơ', 'active'
WHERE NOT EXISTS (SELECT 1 FROM suppliers WHERE supplier_code = 'SUP-001');

INSERT INTO suppliers (supplier_code, supplier_name, contact_name, phone_number, email, address, status)
SELECT 'SUP-002', 'Tân Phát Beverage', 'Trần Thị B', '0911222333', 'sales@tanphatbev.vn', 'TP.HCM', 'active'
WHERE NOT EXISTS (SELECT 1 FROM suppliers WHERE supplier_code = 'SUP-002');

INSERT INTO suppliers (supplier_code, supplier_name, contact_name, phone_number, email, address, status)
SELECT 'SUP-003', 'Hương Trà Việt', 'Lê Văn C', '0988777666', 'info@huongtraviet.vn', 'Đà Nẵng', 'inactive'
WHERE NOT EXISTS (SELECT 1 FROM suppliers WHERE supplier_code = 'SUP-003');

ALTER TABLE items
  ADD COLUMN item_status ENUM('active','inactive') NOT NULL DEFAULT 'active';

UPDATE items
SET item_status = 'active'
WHERE item_status IS NULL;
