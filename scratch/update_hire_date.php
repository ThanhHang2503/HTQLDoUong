<?php
// Tạm thời kết nối database để cập nhật hire_date
require_once dirname(__DIR__) . '/config.php';

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sql = "UPDATE accounts SET hire_date = '2026-01-01'";
if (mysqli_query($conn, $sql)) {
    $affected = mysqli_affected_rows($conn);
    echo "SUCCESS: Đã cập nhật xong $affected bản ghi nhn vin.";
} else {
    echo "ERROR: Lỗi cập nhật: " . mysqli_error($conn);
}
?>
