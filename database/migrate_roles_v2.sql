-- =============================================
-- Migration: Legacy accounts.type -> roles + role_id + status
-- Run this on existing databases (without dropping data)
-- =============================================

USE eldercoffee_db;

CREATE TABLE IF NOT EXISTS roles (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uniq_role_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (name)
SELECT 'admin' WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'admin');
INSERT INTO roles (name)
SELECT 'manager' WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'manager');
INSERT INTO roles (name)
SELECT 'sales' WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'sales');
INSERT INTO roles (name)
SELECT 'warehouse' WHERE NOT EXISTS (SELECT 1 FROM roles WHERE name = 'warehouse');

ALTER TABLE accounts
    ADD COLUMN IF NOT EXISTS role_id INT NULL,
    ADD COLUMN IF NOT EXISTS status ENUM('active','inactive') NOT NULL DEFAULT 'active';

UPDATE accounts
SET status = 'inactive'
WHERE type = 'inactive';

UPDATE accounts a
JOIN roles r ON r.name = 'admin'
SET a.role_id = r.id
WHERE a.role_id IS NULL AND a.type = 'admin';

UPDATE accounts a
JOIN roles r ON r.name = 'sales'
SET a.role_id = r.id
WHERE a.role_id IS NULL AND (a.type = 'user' OR a.type = 'inactive' OR a.type IS NULL OR a.type = '');

UPDATE accounts a
JOIN roles r ON r.name = 'sales'
SET a.role_id = r.id
WHERE a.role_id IS NULL;

ALTER TABLE accounts
    MODIFY COLUMN role_id INT NOT NULL;

ALTER TABLE accounts
    ADD KEY idx_accounts_role (role_id),
    ADD CONSTRAINT fk_accounts_role FOREIGN KEY (role_id) REFERENCES roles(id);

-- Optional cleanup after app code has fully switched to role_id
-- ALTER TABLE accounts DROP COLUMN type;
