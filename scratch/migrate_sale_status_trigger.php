<?php
/**
 * Migration: Thêm trigger tự động cập nhật sale_status khi tồn kho thay đổi
 */
require_once __DIR__ . '/../config.php';

// Drop trigger cũ nếu có
mysqli_query($conn, "DROP TRIGGER IF EXISTS trg_items_sale_status_after_update");

$trigger = "
CREATE TRIGGER trg_items_sale_status_after_update
AFTER UPDATE ON items
FOR EACH ROW
BEGIN
    IF NEW.stock_quantity = 0 AND NEW.sale_status = 'selling' THEN
        UPDATE items SET sale_status = 'stopped' WHERE item_id = NEW.item_id;
    END IF;
END
";

if (mysqli_query($conn, $trigger)) {
    echo "✅ Đã tạo trigger trg_items_sale_status_after_update.\n";
} else {
    echo "❌ Lỗi tạo trigger: " . mysqli_error($conn) . "\n";
    exit(1);
}
echo "🎉 Done!\n";
exit(0);
