<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../src/models/functions.php';
require_once __DIR__ . '/../../src/models/authorization.php';
require_once __DIR__ . '/../../src/hr/Models.php';
require_once __DIR__ . '/../../src/hr/Repositories.php';
require_once __DIR__ . '/../../src/hr/Services.php';

use HR\Services\EmployeeService;

// Bảo mật API: Chỉ cho phép người dùng đã đăng nhập tự sửa chính mình
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
global $conn;

$employeeService = new EmployeeService($conn);
$method = $_SERVER['REQUEST_METHOD'];
$current_uid = currentUserId();

try {
    if ($method === 'PUT' || $method === 'POST') {
        // Hỗ trợ cả POST (nếu client không dùng được PUT) và PUT
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            // Fallback cho form data thông thường nếu JSON fail
            $input = $_POST;
        }

        // Xử lý Thay đổi thông tin cá nhân
        if (isset($input['update_profile'])) {
            $allowedFields = ['full_name', 'phone', 'birth_date', 'address', 'gender'];
            $filteredData = array_intersect_key($input, array_flip($allowedFields));

            if (empty($filteredData)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Không có dữ liệu hợp lệ để cập nhật.']);
                exit;
            }

            $result = $employeeService->updateEmployee($current_uid, $filteredData);
            if ($result['success']) {
                if (!empty($input['full_name'])) {
                    $_SESSION['full_name'] = trim($input['full_name']);
                }
                echo json_encode(['success' => true, 'message' => 'Cập nhật hồ sơ thành công.']);
            } else {
                http_response_code(400); 
                echo json_encode(['success' => false, 'message' => $result['error']]);
            }
            exit;
        }

        // Xử lý Đổi mật khẩu
        if (isset($input['change_password'])) {
            $old_pass = $input['old_password'] ?? '';
            $new_pass = trim($input['new_password'] ?? '');
            $cnf_pass = trim($input['confirm_password'] ?? '');

            if ($new_pass === '' || strlen($new_pass) < 6) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Mật khẩu mới phải có ít nhất 6 ký tự.']);
                exit;
            }
            if ($new_pass !== $cnf_pass) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Xác nhận mật khẩu không khớp.']);
                exit;
            }

            // Lấy hash hiện tại
            $emp = $employeeService->getEmployee($current_uid);
            $stored_hash = $emp['password'] ?? '';

            $verified = password_verify($old_pass, $stored_hash) || (md5($old_pass) === $stored_hash);
            if (!$verified) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng.']);
                exit;
            }

            $updateRes = $employeeService->updateEmployee($current_uid, ['password' => $new_pass]);
            if ($updateRes['success']) {
                echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công.']);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $updateRes['error']]);
            }
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
}
