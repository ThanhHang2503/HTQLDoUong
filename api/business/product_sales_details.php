<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../config.php';
require_once '../../src/models/authorization.php';

// Check permission
if (!can(AppPermission::VIEW_REPORTS) && !can(AppPermission::PROCESS_ORDERS)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$item_id = (int)($_GET['item_id'] ?? 0);
$year    = (int)($_GET['year'] ?? date('Y'));
$month   = (int)($_GET['month'] ?? 0);

if ($item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

$where = "WHERE id.item_id = $item_id AND iv.status != 'cancelled' AND YEAR(iv.creation_time) = $year";
if ($month > 0) {
    $where .= " AND MONTH(iv.creation_time) = $month";
}

$sql = "SELECT iv.invoice_id, c.customer_name, c.phone_number, id.unit_price, id.quantity, (id.unit_price * id.quantity) as line_total
        FROM invoice_details id
        JOIN invoices iv ON iv.invoice_id = id.invoice_id
        JOIN customers c ON c.customer_id = iv.customer_id
        $where
        ORDER BY iv.creation_time DESC";

$result = mysqli_query($conn, $sql);
if (!$result) {
    echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    exit;
}

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'invoice_id' => (int)$row['invoice_id'],
        'customer'   => $row['customer_name'] . ($row['phone_number'] ? ' (' . $row['phone_number'] . ')' : ''),
        'unit_price' => (float)$row['unit_price'],
        'quantity'   => (int)$row['quantity'],
        'line_total' => (float)$row['line_total']
    ];
}

echo json_encode(['success' => true, 'data' => $data]);
