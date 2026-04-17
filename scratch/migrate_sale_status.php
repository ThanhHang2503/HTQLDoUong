<?php
/**
 * Migration: Thêm cột sale_status vào bảng items
 * sale_status: 'selling' (đang bán) | 'stopped' (ngừng bán)
 */
require_once __DIR__ . '/../config.php';

$chk = mysqli_query($conn, "SHOW COLUMNS FROM items LIKE 'sale_status'");
if (mysqli_num_rows($chk) > 0) {
    echo "✅ Cột sale_status đã tồn tại.\n";
    exit(0);
}

// Thêm cột sale_status
$ok = mysqli_query($conn,
    "ALTER TABLE items ADD COLUMN sale_status ENUM('selling','stopped') NOT NULL DEFAULT 'selling' AFTER item_status"
);
if ($ok) {
    echo "✅ Đã thêm cột sale_status.\n";
} else {
    echo "❌ Lỗi thêm cột: " . mysqli_error($conn) . "\n";
    exit(1);
}

// Backfill: sản phẩm inactive hoặc stock=0 → stopped
mysqli_query($conn,
    "UPDATE items SET sale_status = 'stopped' WHERE item_status = 'inactive' OR stock_quantity = 0"
);
echo "✅ Đã backfill sale_status.\n";
echo "\n🎉 Migration hoàn tất!\n";
exit(0);
