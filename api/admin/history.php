<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/models/functions.php';
require_once __DIR__ . '/../../src/models/authorization.php';

if (!isLoggedIn() || !can(AppPermission::MANAGE_ACCOUNTS)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
global $conn;

$account_id = (int)($_GET['account_id'] ?? 0);
if ($account_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

$sql = "SELECT eph.start_date, eph.end_date, p.position_name, p.base_salary, eph.reason
        FROM employee_positions_history eph
        JOIN positions p ON p.position_id = eph.position_id
        WHERE eph.account_id = $account_id
        ORDER BY eph.start_date DESC";
$result = mysqli_query($conn, $sql);
$history = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];

echo json_encode(['success' => true, 'data' => $history]);
