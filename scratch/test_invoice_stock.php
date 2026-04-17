<?php
include 'config.php';
require_once 'src/models/functions.php';

// Prepare data
$customer_name = "Test Customer";
$phone_number = "0987654321";
$account_id = 1;
$list_products = [
    ['product_id' => 1, 'quantity' => 2]
];
$discount = 0;
$total = 100000;

// Check stock before
$res1 = mysqli_query($conn, "SELECT stock_quantity FROM items WHERE item_id = 1");
$before = mysqli_fetch_assoc($res1)['stock_quantity'];
echo "Stock before: $before\n";

// Execute
echo "Creating invoice...\n";
taoHoaDon($conn, $customer_name, $phone_number, $account_id, $list_products, $discount, $total);

// Check stock after
$res2 = mysqli_query($conn, "SELECT stock_quantity FROM items WHERE item_id = 1");
$after = mysqli_fetch_assoc($res2)['stock_quantity'];
echo "Stock after: $after\n";

// Check movement
$res3 = mysqli_query($conn, "SELECT * FROM stock_movements WHERE reference_type = 'invoice' ORDER BY movement_id DESC LIMIT 1");
$mov = mysqli_fetch_assoc($res3);
if ($mov) {
    echo "Movement logged: Quantity change " . $mov['quantity_change'] . "\n";
} else {
    echo "Movement NOT logged!\n";
}
