<?php
require 'config.php';

// Check if invoices exist (double safety check)
$res = mysqli_query($conn, "SELECT COUNT(*) FROM invoices");
$row = mysqli_fetch_row($res);
if ($row[0] > 0) {
    die("Error: Cannot truncate customers because there are " . $row[0] . " invoices linked to them.");
}

// 1. Delete all records from customers table
echo "Deleting records from customers table...\n";
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
if (mysqli_query($conn, "DELETE FROM customers")) {
    mysqli_query($conn, "ALTER TABLE customers AUTO_INCREMENT = 1");
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
    echo "Success.\n";
} else {
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
    die("Error deleting records: " . mysqli_error($conn));
}

// 2. Insert 15 new customers
$new_customers = [
    ['Nguyễn Văn Hải', '0912345678', 'hai.nv@gmail.com', '123 Lê Lợi, Quận 1, TP.HCM'],
    ['Trần Thị Mai', '0987654321', 'mai.tt@yahoo.com', '456 Nguyễn Huệ, Quận 1, TP.HCM'],
    ['Lê Minh Tùng', '0905123456', 'tung.lm@outlook.com', '789 Trần Hưng Đạo, Đà Nẵng'],
    ['Phạm Thanh Hương', '0934123456', 'huong.pt@gmail.com', '101 Hai Bà Trưng, Hà Nội'],
    ['Hoàng Quốc Bảo', '0976123456', 'bao.hq@gmail.com', '202 Lý Tự Trọng, Cần Thơ'],
    ['Đặng Thu Hà', '0945123456', 'ha.dt@vnn.vn', '303 Hùng Vương, Hải Phòng'],
    ['Vũ Minh Quân', '0921123456', 'quan.vm@gmail.com', '404 Điện Biên Phủ, TP.HCM'],
    ['Bùi Gia Huy', '0967123456', 'huy.bg@gmail.com', '505 CMT8, Quận 10, TP.HCM'],
    ['Ngô Phương Thảo', '0901123456', 'thao.np@gmail.com', '606 Phan Xích Long, Phú Nhuận'],
    ['Đỗ Hoàng Long', '0982123456', 'long.dh@gmail.com', '707 Nguyễn Văn Linh, Quận 7'],
    ['Trịnh Hoài Nam', '0911123456', 'nam.th@gmail.com', '808 Võ Văn Kiệt, Quận 5'],
    ['Lý Thị Cẩm Tú', '0919123456', 'tu.ltc@gmail.com', '909 Nguyễn Trãi, Quận 1'],
    ['Đinh Quang Vinh', '0955123456', 'vinh.dq@gmail.com', '111 Bà Huyện Thanh Quan, Quận 3'],
    ['Phan Ngọc Ánh', '0944123456', 'anh.pn@gmail.com', '222 Phổ Quang, Tân Bình'],
    ['Võ Minh Đức', '0933123456', 'duc.vm@gmail.com', '333 Nguyễn Trọng Tuyển, Tân Bình']
];

echo "Inserting 15 new customers...\n";
foreach ($new_customers as $c) {
    $name = mysqli_real_escape_string($conn, $c[0]);
    $phone = mysqli_real_escape_string($conn, $c[1]);
    $email = mysqli_real_escape_string($conn, $c[2]);
    $address = mysqli_real_escape_string($conn, $c[3]);
    
    $sql = "INSERT INTO customers (customer_name, phone_number, email, address) VALUES ('$name', '$phone', '$email', '$address')";
    if (mysqli_query($conn, $sql)) {
        echo "Inserted: $name\n";
    } else {
        echo "Failed: $name - " . mysqli_error($conn) . "\n";
    }
}

echo "\nDone!\n";
