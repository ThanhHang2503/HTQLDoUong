<?php
/**
 * Test đầy đủ GoodsReceiptService::createReceipt()
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/warehouse/Models.php';
require_once __DIR__ . '/../src/warehouse/Repositories.php';
require_once __DIR__ . '/../src/warehouse/Services.php';

$service = new \Warehouse\Services\GoodsReceiptService($conn);

// Lấy supplier_id và item_id hợp lệ
$sup_r = mysqli_query($conn, "SELECT supplier_id FROM suppliers WHERE status='active' LIMIT 1");
$sup   = mysqli_fetch_assoc($sup_r);
$item_r = mysqli_query($conn, "SELECT item_id, item_name, stock_quantity FROM items WHERE item_status='active' LIMIT 1");
$item   = mysqli_fetch_assoc($item_r);

echo "Supplier: {$sup['supplier_id']}\n";
echo "Item: {$item['item_id']} / {$item['item_name']} / stock={$item['stock_quantity']}\n\n";

$result = $service->createReceipt([
    'supplier_id' => (int)$sup['supplier_id'],
    'import_date' => date('Y-m-d'),
    'note'        => 'Test từ CLI',
    'items'       => [
        [
            'item_id'      => (int)$item['item_id'],
            'quantity'     => 5,
            'import_price' => 45000,
            'unit_price'   => 60000,
        ]
    ]
], 1); // created_by = account_id=1

echo "Result:\n";
print_r($result);

if ($result['success']) {
    $rid = $result['receipt_id'];
    echo "\n✅ Tạo phiếu nhập thành công! receipt_id=$rid\n";

    // Verify stock updated
    $stock_r = mysqli_query($conn, "SELECT stock_quantity, sale_status FROM items WHERE item_id={$item['item_id']}");
    $stock   = mysqli_fetch_assoc($stock_r);
    echo "Stock sau nhập: " . $stock['stock_quantity'] . " (was {$item['stock_quantity']}) | sale_status=" . $stock['sale_status'] . "\n";

    // Cleanup test
    mysqli_query($conn, "DELETE FROM stock_movements WHERE reference_type='receipt' AND reference_id=$rid");
    mysqli_query($conn, "DELETE FROM inventory_receipt_items WHERE receipt_id=$rid");
    mysqli_query($conn, "DELETE FROM inventory_receipts WHERE receipt_id=$rid");
    // Restore stock
    mysqli_query($conn, "UPDATE items SET stock_quantity={$item['stock_quantity']} WHERE item_id={$item['item_id']}");
    echo "✅ Cleanup done.\n";
} else {
    echo "\n❌ Thất bại!\n";
}
