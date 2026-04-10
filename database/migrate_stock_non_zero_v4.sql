USE eldercoffee_db;
SET NAMES utf8mb4;

-- Ensure all active products have stock_quantity > 0.
-- Keep existing non-zero stock, only patch zero values.
UPDATE items
SET stock_quantity = 10 + (item_id % 15)
WHERE item_status = 'active'
  AND stock_quantity = 0;

SELECT item_id, item_name, stock_quantity
FROM items
WHERE item_status = 'active'
ORDER BY item_id ASC;
