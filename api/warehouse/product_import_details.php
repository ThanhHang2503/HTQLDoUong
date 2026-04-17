<?php
session_start();
header('Content-Type: application/json');
require_once '../../config.php';
require_once '../../src/models/authorization.php';

// Check permission
if (!can(AppPermission::VIEW_REPORTS) && !can(AppPermission::MANAGE_WAREHOUSE)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$item_id = isset($_GET['item_id']) ? (int)$_GET['item_id'] : 0;
$year    = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$month   = isset($_GET['month']) ? (int)$_GET['month'] : 0;

if ($item_id <= 0 || $year <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$where = "ri.item_id = $item_id AND ir.status = 'completed' AND YEAR(ir.import_date) = $year";
if ($month > 0) {
    $where .= " AND MONTH(ir.import_date) = $month";
}

$sql = "SELECT ir.receipt_code, s.supplier_name, ri.import_price, ri.quantity, ri.line_total, ir.import_date
        FROM inventory_receipt_items ri
        JOIN inventory_receipts ir ON ir.receipt_id = ri.receipt_id
        JOIN suppliers s ON s.supplier_id = ir.supplier_id
        WHERE $where
        ORDER BY ir.import_date DESC";

$result = mysqli_query($conn, $sql);
$details = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Cast numeric values for JSON
        $row['import_price'] = (float)$row['import_price'];
        $row['quantity']     = (int)$row['quantity'];
        $row['line_total']   = (float)$row['line_total'];
        $details[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $details]);
} else {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
}
