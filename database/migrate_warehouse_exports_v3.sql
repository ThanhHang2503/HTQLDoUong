USE eldercoffee_db;
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS inventory_exports (
    export_id INT NOT NULL AUTO_INCREMENT,
    export_code VARCHAR(50) NOT NULL,
    export_date DATE NOT NULL,
    total_value DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    note TEXT DEFAULT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (export_id),
    UNIQUE KEY uniq_export_code (export_code),
    KEY idx_exports_date (export_date),
    KEY idx_exports_created_by (created_by),
    CONSTRAINT fk_exports_created_by FOREIGN KEY (created_by) REFERENCES accounts (account_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_export_items (
    export_item_id INT NOT NULL AUTO_INCREMENT,
    export_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    line_total DECIMAL(14,2) NOT NULL,
    PRIMARY KEY (export_item_id),
    KEY idx_export_items_export (export_id),
    KEY idx_export_items_item (item_id),
    CONSTRAINT fk_export_items_export FOREIGN KEY (export_id) REFERENCES inventory_exports (export_id) ON DELETE CASCADE,
    CONSTRAINT fk_export_items_item FOREIGN KEY (item_id) REFERENCES items (item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
