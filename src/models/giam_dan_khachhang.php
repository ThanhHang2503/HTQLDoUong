<?php
require __DIR__ . '/../../config.php';
$procedure_call = "CALL SortCustomersDescendingByName()";
$stmt = $conn->prepare($procedure_call);
$stmt->execute();
// Lấy kết quả
$result = $stmt->get_result();
$ds_kh_sapxep = $result->fetch_all(MYSQLI_ASSOC);


require_once __DIR__ . '/../views/khachhang.php';
