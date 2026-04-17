<?php
/**
 * Add More Mock Data Script
 * TARGET: eldercoffee_db
 * Adds data for January 2026 and April 2026
 * Does not delete existing data.
 */

header('Content-Type: text/plain; charset=utf-8');

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'eldercoffee_db';

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) die("Connection failed: " . mysqli_connect_error());

// Đảm bảo kết nối dùng utf8mb4
mysqli_set_charset($conn, 'utf8mb4');
mysqli_query($conn, "SET NAMES 'utf8mb4'");

echo "Starting data generation...\n";

// Fetch master data
$suppliers = [];
$res = mysqli_query($conn, "SELECT supplier_id FROM suppliers");
while ($row = mysqli_fetch_assoc($res)) $suppliers[] = $row['supplier_id'];

$customers = [];
$res = mysqli_query($conn, "SELECT customer_id FROM customers");
while ($row = mysqli_fetch_assoc($res)) $customers[] = $row['customer_id'];

$accounts = [];
$res = mysqli_query($conn, "SELECT account_id FROM accounts");
while ($row = mysqli_fetch_assoc($res)) $accounts[] = $row['account_id'];

if (empty($accounts)) die("No accounts found. Please create an account first.\n");
$account_id = $accounts[array_rand($accounts)];

$items = [];
$current_stock = [];
$res = mysqli_query($conn, "SELECT item_id, purchase_price, unit_price, stock_quantity FROM items");
while ($row = mysqli_fetch_assoc($res)) {
    $items[] = $row;
    $current_stock[$row['item_id']] = (int)$row['stock_quantity'];
}

$months = [
    ['month' => '01', 'year' => '2026', 'receipts' => 5, 'invoices' => 7],
    ['month' => '04', 'year' => '2026', 'receipts' => 8, 'invoices' => 10],
];

// Get max receipt code number to avoid duplicates
$res = mysqli_query($conn, "SELECT receipt_code FROM inventory_receipts ORDER BY receipt_id DESC LIMIT 1");
$last_receipt = mysqli_fetch_assoc($res);
$receipt_counter = 100;
if ($last_receipt && preg_match('/PN\d{4}(\d{3})/', $last_receipt['receipt_code'], $matches)) {
    $receipt_counter = (int)$matches[1] + 1;
}

foreach ($months as $m) {
    echo "--- Generating data for {$m['month']}/{$m['year']} ---\n";
    
    // 1. Receipts (Imports)
    $receipt_dates = [];
    for ($i = 0; $i < $m['receipts']; $i++) {
        $day = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
        $receipt_dates[] = "{$m['year']}-{$m['month']}-{$day}";
    }
    sort($receipt_dates);
    
    foreach ($receipt_dates as $date) {
        $sup_id = $suppliers[array_rand($suppliers)];
        $code_month = substr($m['year'], 2, 2) . $m['month'];
        $receipt_code = "PN{$code_month}" . str_pad($receipt_counter++, 3, '0', STR_PAD_LEFT);
        
        mysqli_query($conn, "INSERT INTO inventory_receipts (receipt_code, supplier_id, import_date, total_value, status, created_by) 
                             VALUES ('$receipt_code', $sup_id, '$date', 0, 'completed', $account_id)");
        $r_id = mysqli_insert_id($conn);
        
        $total_val = 0;
        $num_items = rand(2, 5);
        $shuffled_items = $items;
        shuffle($shuffled_items);
        
        for ($i = 0; $i < $num_items; $i++) {
            $item = $shuffled_items[$i];
            $qty = rand(30, 80);
            $line_total = $qty * $item['purchase_price'];
            $total_val += $line_total;
            
            mysqli_query($conn, "INSERT INTO inventory_receipt_items (receipt_id, item_id, quantity, remaining_qty, import_price, unit_price, line_total) 
                                 VALUES ($r_id, {$item['item_id']}, $qty, $qty, {$item['purchase_price']}, {$item['unit_price']}, $line_total)");
            
            // Update stock
            mysqli_query($conn, "UPDATE items SET stock_quantity = stock_quantity + $qty WHERE item_id = {$item['item_id']}");
            
            // Stock movement
            mysqli_query($conn, "INSERT INTO stock_movements (item_id, movement_type, quantity_change, stock_before, stock_after, unit_cost, reference_type, reference_id, created_by) 
                                 VALUES ({$item['item_id']}, 'import', $qty, {$current_stock[$item['item_id']]}, ".($current_stock[$item['item_id']] + $qty).", {$item['purchase_price']}, 'inventory_receipts', $r_id, $account_id)");
                                 
            $current_stock[$item['item_id']] += $qty;
        }
        mysqli_query($conn, "UPDATE inventory_receipts SET total_value = $total_val WHERE receipt_id = $r_id");
    }
    echo "Added {$m['receipts']} receipts.\n";
    
    // 2. Invoices (Sales)
    $invoice_dates = [];
    for ($i = 0; $i < $m['invoices']; $i++) {
        $day = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
        $time = str_pad(rand(8, 22), 2, '0', STR_PAD_LEFT) . ':' . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT) . ':00';
        $invoice_dates[] = "{$m['year']}-{$m['month']}-{$day} {$time}";
    }
    sort($invoice_dates);
    
    foreach ($invoice_dates as $date) {
        $c_id = $customers[array_rand($customers)];
        mysqli_query($conn, "INSERT INTO invoices (account_id, customer_id, discount, total, status, creation_time) 
                             VALUES ($account_id, $c_id, 0, 0, 'completed', '$date')");
        $inv_id = mysqli_insert_id($conn);
        
        $total_inv = 0;
        $num_items = rand(1, 4);
        $shuffled_items = $items;
        shuffle($shuffled_items);
        
        $added = 0;
        for ($i = 0; $i < count($shuffled_items) && $added < $num_items; $i++) {
            $item = $shuffled_items[$i];
            
            if ($current_stock[$item['item_id']] > 0) {
                $max_qty = min(5, $current_stock[$item['item_id']]);
                $qty = rand(1, $max_qty);
                $total_inv += $qty * $item['unit_price'];
                
                mysqli_query($conn, "INSERT INTO invoice_details (invoice_id, item_id, quantity, unit_price) 
                                     VALUES ($inv_id, {$item['item_id']}, $qty, {$item['unit_price']})");
                
                // Update stock
                mysqli_query($conn, "UPDATE items SET stock_quantity = stock_quantity - $qty WHERE item_id = {$item['item_id']}");
                
                // Stock movement
                mysqli_query($conn, "INSERT INTO stock_movements (item_id, movement_type, quantity_change, stock_before, stock_after, unit_cost, reference_type, reference_id, created_by) 
                                     VALUES ({$item['item_id']}, 'export', -$qty, {$current_stock[$item['item_id']]}, ".($current_stock[$item['item_id']] - $qty).", {$item['purchase_price']}, 'invoices', $inv_id, $account_id)");
                                     
                $current_stock[$item['item_id']] -= $qty;
                $added++;
            }
        }
        mysqli_query($conn, "UPDATE invoices SET total = $total_inv WHERE invoice_id = $inv_id");
    }
    echo "Added {$m['invoices']} invoices.\n";
}

echo "DATA ADDITION SUCCESSFUL!\n";
?>
