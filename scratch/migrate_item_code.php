<?php
/**
 * Migration: Thêm cột item_code vào bảng items
 * Mã tự sinh dạng ITM-00001, ITM-00002, ...
 */
require_once __DIR__ . '/../config.php';

$errors = [];
$steps  = [];

// ─── 1. Kiểm tra cột item_code đã tồn tại chưa ───────────────────
$chk = mysqli_query($conn, "SHOW COLUMNS FROM items LIKE 'item_code'");
if (mysqli_num_rows($chk) > 0) {
    echo "✅ Cột item_code đã tồn tại. Không cần migrate thêm.\n";
    exit(0);
}

// ─── 2. Thêm cột item_code sau item_id ───────────────────────────
$sql = "ALTER TABLE items ADD COLUMN item_code VARCHAR(20) NULL AFTER item_id";
if (mysqli_query($conn, $sql)) {
    $steps[] = "✅ Đã thêm cột item_code.";
} else {
    $errors[] = "❌ Lỗi thêm cột: " . mysqli_error($conn);
}

// ─── 3. Backfill item_code cho sản phẩm hiện có ──────────────────
$rows = mysqli_query($conn, "SELECT item_id FROM items ORDER BY item_id ASC");
$counter = 1;
while ($row = mysqli_fetch_assoc($rows)) {
    $code = 'ITM-' . str_pad($counter, 5, '0', STR_PAD_LEFT);
    $id   = (int)$row['item_id'];
    if (!mysqli_query($conn, "UPDATE items SET item_code='$code' WHERE item_id=$id")) {
        $errors[] = "❌ Lỗi backfill item_id=$id: " . mysqli_error($conn);
    }
    $counter++;
}
$steps[] = "✅ Đã backfill item_code cho " . ($counter - 1) . " sản phẩm.";

// ─── 4. Đặt cột NOT NULL + UNIQUE ────────────────────────────────
$sqls = [
    "ALTER TABLE items MODIFY COLUMN item_code VARCHAR(20) NOT NULL",
    "ALTER TABLE items ADD UNIQUE KEY uq_item_code (item_code)",
];
foreach ($sqls as $s) {
    if (mysqli_query($conn, $s)) {
        $steps[] = "✅ " . rtrim($s, ';');
    } else {
        $errors[] = "❌ " . rtrim($s, ';') . " — " . mysqli_error($conn);
    }
}

// ─── 5. Tạo trigger tự sinh item_code khi INSERT ─────────────────
// Drop trigger cũ nếu có
mysqli_query($conn, "DROP TRIGGER IF EXISTS trg_items_before_insert");

$trigger = "
CREATE TRIGGER trg_items_before_insert
BEFORE INSERT ON items
FOR EACH ROW
BEGIN
    DECLARE next_code VARCHAR(20);
    DECLARE max_num  INT DEFAULT 0;
    SELECT COALESCE(MAX(CAST(SUBSTRING(item_code, 5) AS UNSIGNED)), 0)
      INTO max_num
      FROM items
     WHERE item_code REGEXP '^ITM-[0-9]+\$';
    SET next_code = CONCAT('ITM-', LPAD(max_num + 1, 5, '0'));
    SET NEW.item_code = next_code;
END
";

if (mysqli_query($conn, $trigger)) {
    $steps[] = "✅ Đã tạo trigger trg_items_before_insert.";
} else {
    $errors[] = "❌ Lỗi tạo trigger: " . mysqli_error($conn);
}

// ─── Kết quả ──────────────────────────────────────────────────────
foreach ($steps as $s) echo $s . PHP_EOL;
if ($errors) {
    foreach ($errors as $e) echo $e . PHP_EOL;
    exit(1);
}
echo PHP_EOL . "🎉 Migration hoàn tất!\n";
exit(0);
