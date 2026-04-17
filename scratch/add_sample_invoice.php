<?php
include 'config.php';
require_once 'src/models/functions.php';

// Prepare test data
$customer_name = "Nguyễn Văn A";
$phone_number = "0912345678";
$account_id = 1; // Assuming 1 is a valid account_id (admin/manager)
$discount = 15000;
$total = 200000;

$list_products = [
    ['product_id' => 1, 'quantity' => 2],
    ['product_id' => 2, 'quantity' => 3],
    ['product_id' => 3, 'quantity' => 1]
];

echo "Adding test invoice for $customer_name...\n";

// Execute the unified taoHoaDon function which handles:
// - Customer creation/lookup
// - Invoice header creation
// - Invoice details creation (with unit_price)
// - Stock reduction
// - Movement logging
// - Product status updates

try {
    taoHoaDon($conn, $customer_name, $phone_number, $account_id, $list_products, $discount, $total);
    echo "Success! Invoice created and stock updated.\n";
} catch (Exception $e) {
    echo "Failed: " . $e->getMessage() . "\n";
}
