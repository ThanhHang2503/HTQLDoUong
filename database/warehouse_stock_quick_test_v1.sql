USE eldercoffee_db;
SET NAMES utf8mb4;

-- Quick validation script for stock rules.
-- This script uses one transaction and ROLLBACK so test data is not persisted.

START TRANSACTION;

SET @test_item_id := (
		SELECT item_id
		FROM items
		WHERE item_status = 'active'
		ORDER BY item_id ASC
		LIMIT 1
);

SET @test_account_id := (
		SELECT account_id
		FROM accounts
		WHERE status = 'active'
		ORDER BY account_id ASC
		LIMIT 1
);

SET @test_customer_id := (
		SELECT customer_id
		FROM customers
		ORDER BY customer_id ASC
		LIMIT 1
);

SELECT 'TEST_CONTEXT' AS step, @test_item_id AS test_item_id, @test_account_id AS test_account_id, @test_customer_id AS test_customer_id;

SELECT stock_quantity INTO @stock_before
FROM items
WHERE item_id = @test_item_id;

SELECT 'BASE_STOCK' AS step, @stock_before AS stock_before;

-- Ensure enough stock to run trigger checks.
UPDATE items
SET stock_quantity = 10
WHERE item_id = @test_item_id;

SELECT stock_quantity INTO @stock_set_10
FROM items
WHERE item_id = @test_item_id;

SELECT 'SET_STOCK_10' AS step, @stock_set_10 AS stock_after_set;

INSERT INTO invoices (account_id, customer_id, discount, total)
VALUES (@test_account_id, @test_customer_id, 0, 0);

SET @test_invoice_id := LAST_INSERT_ID();

INSERT INTO invoice_details (invoice_id, item_id, quantity)
VALUES (@test_invoice_id, @test_item_id, 3);

SELECT stock_quantity INTO @stock_after_insert
FROM items
WHERE item_id = @test_item_id;

SELECT 'INVOICE_INSERT_TRIGGER' AS step, @stock_after_insert AS stock_after_insert, 7 AS expected_stock;

UPDATE invoice_details
SET quantity = 5
WHERE invoice_id = @test_invoice_id
	AND item_id = @test_item_id
LIMIT 1;

SELECT stock_quantity INTO @stock_after_update
FROM items
WHERE item_id = @test_item_id;

SELECT 'INVOICE_UPDATE_TRIGGER' AS step, @stock_after_update AS stock_after_update, 5 AS expected_stock;

DELETE FROM invoice_details
WHERE invoice_id = @test_invoice_id
	AND item_id = @test_item_id
LIMIT 1;

SELECT stock_quantity INTO @stock_after_delete
FROM items
WHERE item_id = @test_item_id;

SELECT 'INVOICE_DELETE_TRIGGER' AS step, @stock_after_delete AS stock_after_delete, 10 AS expected_stock;

SELECT
		'STRUCTURE_CHECK' AS step,
		EXISTS (
				SELECT 1
				FROM information_schema.columns
				WHERE table_schema = DATABASE()
					AND table_name = 'items'
					AND column_name = 'stock_quantity'
		) AS has_stock_column,
		EXISTS (
				SELECT 1
				FROM information_schema.triggers
				WHERE trigger_schema = DATABASE()
					AND trigger_name = 'trg_invoice_details_before_insert_stock'
		) AS has_invoice_insert_trigger,
		EXISTS (
				SELECT 1
				FROM information_schema.tables
				WHERE table_schema = DATABASE()
					AND table_name = 'inventory_exports'
		) AS has_inventory_exports;

ROLLBACK;

SELECT 'DONE_ROLLBACK' AS step;
