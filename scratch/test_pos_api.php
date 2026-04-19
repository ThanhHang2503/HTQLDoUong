<?php
/**
 * Test Chức vụ History API
 */
require_once __DIR__ . '/../config.php';
session_start();
$_SESSION['account_id'] = 1; // Fake login

function testApi($accId) {
    global $conn;
    // Imitating the API logic in user_page.php
    $acc_id = (int)$accId;
    $hr = mysqli_query($conn, "SELECT eph.start_date, eph.end_date, p.position_name, eph.reason
        FROM employee_positions_history eph
        JOIN positions p ON p.position_id = eph.position_id
        WHERE eph.account_id = $acc_id ORDER BY eph.start_date DESC");
    $hist = $hr ? mysqli_fetch_all($hr, MYSQLI_ASSOC) : [];

    if (empty($hist)) {
        $sql = "SELECT a.hire_date, p.position_name FROM accounts a 
                JOIN positions p ON p.position_id = a.position_id 
                WHERE a.account_id = $acc_id";
        $qr = mysqli_query($conn, $sql);
        if ($qr && $row = mysqli_fetch_assoc($qr)) {
            $hist[] = [
                'start_date' => $row['hire_date'] ?? '2000-01-01',
                'end_date' => null,
                'position_name' => $row['position_name'],
                'reason' => 'Bổ nhiệm khi vào làm'
            ];
        }
    }
    echo "History for #$accId:\n";
    print_r($hist);
}

testApi(1);
?>
