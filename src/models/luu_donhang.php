<?php

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/../../config.php';

if (isset($_POST)) {
    $account_id = $_POST["account_id"];
    $customer_name = $_POST["customer_name"];
    $phone_number = $_POST["phone_number"];
    $total = $_POST["total"];
    $discount = $_POST["discount2"];

    $products = $_POST['product_details'];

    $products = $products[0];
    $pairs = explode(",", $products);

    // Mảng kết quả
    $result = [];

    foreach ($pairs as $pair) {
        // Phân tách cặp product_id và quantity
        $parts = explode(":", $pair);

        // Tạo mảng con chứa product_id và quantity
        $subarray = [
            'product_id' => $parts[0],
            'quantity' => $parts[1]
        ];

        // Thêm mảng con vào mảng kết quả
        $result[] = $subarray;
    }

    taoHoaDon($conn,$customer_name, $phone_number, $account_id, $result, $discount, $total);
    $_SESSION['tao_don_hang_thanh_cong'] = 'Bạn đã tạo 1 đơn hàng thành công';
    redirect('user_page.php?donhang');
}
