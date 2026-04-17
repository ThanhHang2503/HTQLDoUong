<?php
/**
 * Database Reset and Vietnamese Data Seeding Script
 * TARGET: eldercoffee_db
 * PRESERVED: accounts, roles, positions
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

echo "Starting Database Reset...\n";

// 1. Reset Phase
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0;");

$tables_to_truncate = [
    'invoice_details', 'invoices',
    'inventory_receipt_items', 'inventory_receipts',
    'inventory_export_items', 'inventory_exports',
    'stock_movements',
    'items', 'category',
    'customers', 'suppliers',
    'leave_requests', 'resignation_requests', 'salary_records', 'employee_positions_history'
];

foreach ($tables_to_truncate as $t) {
    if (mysqli_query($conn, "TRUNCATE TABLE `$t`")) {
        echo "Truncated $t\n";
    } else {
        echo "Error truncating $t: " . mysqli_error($conn) . "\n";
    }
}

mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1;");
echo "Reset complete.\n\n";

// 2. Seeding Phase
echo "Seeding Master Data...\n";

// Categories
$categories = ['Cà phê', 'Trà sữa', 'Trà trái cây', 'Đá xay', 'Bánh ngọt'];
$cat_ids = [];
foreach ($categories as $cat) {
    mysqli_query($conn, "INSERT INTO category (category_name) VALUES ('$cat')");
    $cat_ids[$cat] = mysqli_insert_id($conn);
}

// Suppliers
$suppliers = [
    ['RN001', 'Tổng kho Nguyên liệu Sài Gòn', 'Anh Tú', '0901234567', 'saigon.nguyenlieu@gmail.com', '123 Đường số 7, Q. Bình Tân, TP.HCM'],
    ['RN002', 'Đại lý Cà phê Buôn Ma Thuột', 'Chị Lan', '0912345678', 'bm.coffee@yahoo.com', '45 Phan Bội Châu, TP. Buôn Ma Thuột'],
    ['RN003', 'Cung ứng Sữa Vinamilk Chi nhánh 2', 'Anh Hùng', '0987654321', 'vnm.cn2@vinamilk.com.vn', '10 Tân Trào, Q.7, TP.HCM'],
    ['RN004', 'Thế giới Trà Thái & Topping', 'Anh Minh', '0933445566', 'trathai.sg@gmail.com', '78 Cách Mạng Tháng 8, Q.3, TP.HCM'],
    ['RN005', 'Bao bì & Dụng cụ quầy bar', 'Chị Hoa', '0944556677', 'baobi.coffee@gmail.com', '22 Hòa Bình, Q. Tân Phú, TP.HCM']
];
$sup_ids = [];
foreach ($suppliers as $s) {
    mysqli_query($conn, "INSERT INTO suppliers (supplier_code, supplier_name, contact_name, phone_number, email, address, status) VALUES ('$s[0]', '$s[1]', '$s[2]', '$s[3]', '$s[4]', '$s[5]', 'active')");
    $sup_ids[] = mysqli_insert_id($conn);
}

// Customers
$customers = [
    ['Nguyễn Văn An', '0381112223', 'an.nv@gmail.com', 'P12, Q.Gò Vấp, TP.HCM'],
    ['Lê Thị Mai', '0382223334', 'mai.lt@gmail.com', 'Q. Bình Thạnh, TP.HCM'],
    ['Trần Quang Hải', '0383334445', 'hai.tq@gmail.com', 'Q. Phú Nhuận, TP.HCM'],
    ['Phạm Thu Thảo', '0384445556', 'thao.pt@gmail.com', 'Q.3, TP.HCM'],
    ['Đỗ Minh Đức', '0385556667', 'duc.dm@gmail.com', 'Q. Tân Bình, TP.HCM'],
    ['Ngô Gia Bảo', '0386667778', 'bao.ng@gmail.com', 'Q.7, TP.HCM'],
    ['Vũ Thanh Hằng', '0387778889', 'hang.vt@gmail.com', 'Q.1, TP.HCM'],
    ['Lý Tiểu Long', '0388889990', 'long.ly@gmail.com', 'Q. Thủ Đức, TP.HCM'],
    ['Phan Thị Ánh', '0389990001', 'anh.pt@gmail.com', 'Q.2, TP.HCM'],
    ['Bùi Tiến Dũng', '0380001112', 'dung.bt@gmail.com', 'Q. Hóc Môn, TP.HCM']
];
$cust_ids = [];
foreach ($customers as $c) {
    mysqli_query($conn, "INSERT INTO customers (customer_name, phone_number, email, address) VALUES ('$c[0]', '$c[1]', '$c[2]', '$c[3]')");
    $cust_ids[] = mysqli_insert_id($conn);
}

// Items
$items_data = [
    ['CF001', 'Cà phê đen đá', 'Cà phê rang xay nguyên chất, đậm đà.', 5000, 25000, 'Cà phê'],
    ['CF002', 'Cà phê sữa đá', 'Cà phê quyện cùng sữa đặc cao cấp.', 7000, 30000, 'Cà phê'],
    ['TS001', 'Trà sữa truyền thống', 'Vị trà đậm cùng sữa béo ngậy.', 15000, 35000, 'Trà sữa'],
    ['TS002', 'Trà sữa Thái xanh', 'Trà Thái xanh mát lạnh kèm trân châu.', 16000, 40000, 'Trà sữa'],
    ['TS003', 'Hồng trà sữa', 'Hồng trà thanh khiết pha cùng sữa.', 15000, 35000, 'Trà sữa'],
    ['TT001', 'Trà đào cam sả', 'Đào miếng giòn rụm cùng hương cam sả.', 12000, 45000, 'Trà trái cây'],
    ['TT002', 'Trà vải lài', 'Hương vải thơm nồng cùng cốt trà lài.', 12000, 45000, 'Trà trái cây'],
    ['DX001', 'Cookie đá xay', 'Bánh oreo xay cùng sữa và kem béo.', 18000, 50000, 'Đá xay'],
    ['DX002', 'Matcha đá xay', 'Bột matcha Nhật Bản nguyên chất.', 20000, 55000, 'Đá xay'],
    ['BT001', 'Bánh Tiramisu', 'Cốt bánh mềm mịn, vị cà phê đắng nhẹ.', 25000, 55000, 'Bánh ngọt'],
    ['BT002', 'Bánh Croissant', 'Bánh sừng bò ngàn lớp thơm mùi bơ.', 15000, 30000, 'Bánh ngọt'],
    ['BT003', 'Bánh Phô mai nướng', 'Vị phô mai béo ngậy tan trong miệng.', 28000, 60000, 'Bánh ngọt']
];
$item_ids = [];
foreach ($items_data as $i) {
    $cat_id = $cat_ids[$i[5]];
    mysqli_query($conn, "INSERT INTO items (item_code, item_name, description, purchase_price, unit_price, category_id, stock_quantity, item_status, sale_status) 
                         VALUES ('$i[0]', '$i[1]', '$i[2]', $i[3], $i[4], $cat_id, 0, 'active', 'selling')");
    $item_ids[] = [
        'id' => mysqli_insert_id($conn),
        'purchase_price' => $i[3],
        'unit_price' => $i[4]
    ];
}

echo "Master data seeded.\n\n";

// Get a valid account_id for admin/staff
$staff_res = mysqli_query($conn, "SELECT account_id FROM accounts LIMIT 1");
$staff = mysqli_fetch_assoc($staff_res);
$account_id = $staff['account_id'];

// 3. Transactions Seeding
echo "Seeding Receipts (Imports)...\n";

$receipt_codes = ['PN2601001', 'PN2601002', 'PN2602001', 'PN2602002', 'PN2602003', 'PN2603001', 'PN2603002', 'PN2603003', 'PN2603004', 'PN2603005'];
$current_stock = array_fill_keys(array_column($item_ids, 'id'), 0);

foreach ($receipt_codes as $idx => $code) {
    $sup_id = $sup_ids[array_rand($sup_ids)];
    $day = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
    $month = ($idx < 2) ? '01' : (($idx < 5) ? '02' : '03');
    $date = "2026-$month-$day";
    
    mysqli_query($conn, "INSERT INTO inventory_receipts (receipt_code, supplier_id, import_date, total_value, status, created_by) 
                         VALUES ('$code', $sup_id, '$date', 0, 'completed', $account_id)");
    $r_id = mysqli_insert_id($conn);
    
    $total_val = 0;
    $shuffled_items = $item_ids;
    shuffle($shuffled_items);
    $num_items = rand(2, 4);
    for ($i = 0; $i < $num_items; $i++) {
        $item = $shuffled_items[$i];
        $qty = rand(50, 100);
        $line_total = $qty * $item['purchase_price'];
        $total_val += $line_total;
        
        mysqli_query($conn, "INSERT INTO inventory_receipt_items (receipt_id, item_id, quantity, remaining_qty, import_price, unit_price, line_total) 
                             VALUES ($r_id, {$item['id']}, $qty, $qty, {$item['purchase_price']}, {$item['unit_price']}, $line_total)");
        
        // Update stock
        mysqli_query($conn, "UPDATE items SET stock_quantity = stock_quantity + $qty WHERE item_id = {$item['id']}");
        $current_stock[$item['id']] += $qty;
        
        // Stock movement
        mysqli_query($conn, "INSERT INTO stock_movements (item_id, movement_type, quantity_change, stock_before, stock_after, unit_cost, reference_type, reference_id, created_by) 
                             VALUES ({$item['id']}, 'import', $qty, ".($current_stock[$item['id']] - $qty).", {$current_stock[$item['id']]}, {$item['purchase_price']}, 'inventory_receipts', $r_id, $account_id)");
    }
    mysqli_query($conn, "UPDATE inventory_receipts SET total_value = $total_val WHERE receipt_id = $r_id");
}

echo "Receipts seeded.\n\n";

echo "Seeding Invoices (Sales)...\n";
$invoice_dates = [];
for($i=0; $i<15; $i++) {
    $day = str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
    $month = (rand(0, 1) == 0) ? '02' : '03'; // Feb or March
    $invoice_dates[] = "2026-$month-$day " . str_pad(rand(8, 21), 2, '0', STR_PAD_LEFT) . ":" . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT) . ":00";
}
sort($invoice_dates);

foreach ($invoice_dates as $date) {
    $c_id = $cust_ids[array_rand($cust_ids)];
    mysqli_query($conn, "INSERT INTO invoices (account_id, customer_id, discount, total, status, creation_time) 
                         VALUES ($account_id, $c_id, 0, 0, 'completed', '$date')");
    $inv_id = mysqli_insert_id($conn);
    
    $total_inv = 0;
    $num_items = rand(1, 3);
    $shuffled_items = $item_ids;
    shuffle($shuffled_items);
    
    $added = 0;
    for ($i = 0; $i < count($shuffled_items) && $added < $num_items; $i++) {
        $item = $shuffled_items[$i];
        if ($current_stock[$item['id']] > 5) {
            $qty = rand(1, 5);
            $total_inv += $qty * $item['unit_price'];
            
            mysqli_query($conn, "INSERT INTO invoice_details (invoice_id, item_id, quantity, unit_price) 
                                 VALUES ($inv_id, {$item['id']}, $qty, {$item['unit_price']})");
            
            // Update stock
            mysqli_query($conn, "UPDATE items SET stock_quantity = stock_quantity - $qty WHERE item_id = {$item['id']}");
            $current_stock[$item['id']] -= $qty;
            
            // Stock movement
            mysqli_query($conn, "INSERT INTO stock_movements (item_id, movement_type, quantity_change, stock_before, stock_after, unit_cost, reference_type, reference_id, created_by) 
                                 VALUES ({$item['id']}, 'export', -$qty, ".($current_stock[$item['id']] + $qty).", {$current_stock[$item['id']]}, {$item['purchase_price']}, 'invoices', $inv_id, $account_id)");
            $added++;
        }
    }
    mysqli_query($conn, "UPDATE invoices SET total = $total_inv WHERE invoice_id = $inv_id");
}

echo "Invoices seeded.\n\n";
echo "DATABASE RE-POPULATION SUCCESSFUL!\n";
?>
