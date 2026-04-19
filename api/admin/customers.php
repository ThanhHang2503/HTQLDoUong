<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/models/functions.php';
require_once __DIR__ . '/../../src/models/authorization.php';

// Bảo mật API: Chỉ những người có quyền quản lý khách hàng mới được truy cập
if (!isLoggedIn() || !can(AppPermission::MANAGE_CUSTOMERS)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
global $conn;

$method = $_SERVER['REQUEST_METHOD'];

try {
    // Đọc dữ liệu từ request body (JSON)
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if ($method === 'POST' || $method === 'PUT') {
        $name = trim((string)($data['customer_name'] ?? ''));
        $phone = trim((string)($data['phone_number'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));
        $address = trim((string)($data['address'] ?? ''));
        $id = (int)($data['customer_id'] ?? 0);

        // --- BACKEND VALIDATION (Bắt buộc) ---
        $errors = [];

        // 1. Tên khách hàng
        if ($name === '') {
            $errors['customer_name'] = 'Tên khách hàng không được để trống.';
        }

        // 2. Số điện thoại (Chỉ cho phép số 0-9, độ dài 9-11)
        if ($phone === '') {
            $errors['phone_number'] = 'Số điện thoại không được để trống.';
        } elseif (!preg_match('/^[0-9]{9,11}$/', $phone)) {
            $errors['phone_number'] = 'Số điện thoại không hợp lệ (phải từ 9-11 chữ số).';
        }

        // 3. Email (Đúng định dạng email)
        if ($email !== '' && !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email)) {
            $errors['email'] = 'Email không đúng định dạng.';
        }

        // Nếu có lỗi, trả về HTTP 400 và danh sách lỗi
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

        // --- XỬ LÝ DATABASE ---
        $safe_name = mysqli_real_escape_string($conn, $name);
        $safe_phone = mysqli_real_escape_string($conn, $phone);
        $safe_email = mysqli_real_escape_string($conn, $email);
        $safe_address = mysqli_real_escape_string($conn, $address);

        // Kiểm tra trùng số điện thoại (Phone is Unique)
        $dup_sql = "SELECT customer_id FROM customers WHERE phone_number = '$safe_phone'";
        if ($method === 'PUT') {
            $dup_sql .= " AND customer_id != $id";
        }
        $dup_res = mysqli_query($conn, $dup_sql);
        if ($dup_res && mysqli_num_rows($dup_res) > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'errors' => ['phone_number' => 'Số điện thoại này đã được sử dụng bởi khách hàng khác.']]);
            exit;
        }

        if ($method === 'POST') {
            $sql = "INSERT INTO customers (customer_name, phone_number, email, address) 
                    VALUES ('$safe_name', '$safe_phone', '$safe_email', '$safe_address')";
            if (mysqli_query($conn, $sql)) {
                http_response_code(201);
                echo json_encode(['success' => true, 'message' => 'Thêm khách hàng thành công.', 'id' => mysqli_insert_id($conn)]);
            } else {
                throw new Exception('Không thể thêm khách hàng: ' . mysqli_error($conn));
            }
        } elseif ($method === 'PUT') {
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID khách hàng không hợp lệ.']);
                exit;
            }
            $sql = "UPDATE customers SET 
                        customer_name = '$safe_name', 
                        phone_number = '$safe_phone', 
                        email = '$safe_email', 
                        address = '$safe_address' 
                    WHERE customer_id = $id";
            if (mysqli_query($conn, $sql)) {
                echo json_encode(['success' => true, 'message' => 'Cập nhật thông tin khách hàng thành công.']);
            } else {
                throw new Exception('Không thể cập nhật khách hàng: ' . mysqli_error($conn));
            }
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
